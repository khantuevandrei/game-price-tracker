<?php

namespace App\Console\Commands;

use App\Mail\VerificationCodeMail;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Services\SteamService;
use App\Models\Game;

#[Signature('app:telegram-polling')]
#[Description('Command description')]
class TelegramPolling extends Command
{
    private function sendReply(string $chatId, string $reply): void
    {
        $token = env('TELEGRAM_BOT_TOKEN');

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $reply
        ]);
    }

    private function getUser(string $chatId): ?User
    {
        return User::where('telegram_id', $chatId)->first();
    }

    private function getTrackedGames(User $user)
    {
        return $user->trackedGames()->with('game')->get();
    }

    private function handleCancel(string $chatId): void
    {
        Cache::forget('tg_state:' . $chatId);
        $this->sendReply($chatId, 'Действие отменено.');
        return;
    }

    private function handleStart(string $chatId, array $message): void
    {
        $user = $this->getUser($chatId);

        if (!$user) {
            $firstName = $message['chat']['first_name'] ?? '';
            $lastName = $message['chat']['last_name'] ?? '';
            $name = trim($firstName . ' ' . $lastName) ?: 'Telegram User';

            $user = User::create([
                'name' => $name,
                'email' => 'tg_' . $chatId . '@telegram.local',
                'password' => bcrypt(bin2hex(random_bytes(8))),
                'telegram_id' => $chatId,
            ]);
        }

        $reply = "Привет! Это бот для отслеживания цен на игры в Steam.\n\n
            Доступные команды:\n
            /start - приветствие\n
            /email - привязать email\n
            /search - найти игру\n
            /track - отслеживать игру\n
            /list - посмотреть отслеживаемые игры\n
            /price - посмотреть информацию об игре\n
            /set - установить цель по цене\n
            /untrack - прекратить отслеживание\n
            /notify - вкл/откл уведомления\n
            /cancel - отмена текущей команды\n
            /help - показать команды";

        $this->sendReply($chatId, $reply);
    }

    private function handleEmail(string $chatId): void
    {
        $user = $this->getUser($chatId);
        $currentEmail = $user->email ?? null;

        Cache::put('tg_state:' . $chatId, 'awaiting_email', 300);

        if ($currentEmail && !str_starts_with($currentEmail, 'tg_')) {
            $reply = "Текущий email: {$currentEmail}\n\nВведи новый email для смены.\n\n/cancel для отмены.";
        } else {
            $reply = "Введи email.\n\n/cancel для отмены.";
        }

        $this->sendReply($chatId, $reply);
    }

    private function handleSearch(string $chatId): void
    {
        Cache::put('tg_state:' . $chatId, 'awaiting_search', 300);
        $reply = "Введи название игры.\n\n/cancel для отмены.";
        $this->sendReply($chatId, $reply);
    }

    private function handleTrack(string $chatId, string $text): void
    {
        $parts = explode(' ', $text);
        $index = (int) ($parts[1] ?? 0) - 1;

        if ($index < 0) {
            $reply = 'Использование: /track 1';
            $this->sendReply($chatId, $reply);
            return;
        }

        $results = Cache::get('tg_search:' . $chatId);

        if (!$results || !isset($results[$index])) {
            $reply = 'Сначала выполни поиск через /search';
            $this->sendReply($chatId, $reply);
            return;
        }

        $game = $results[$index];
        $appId = $game['id'];

        $details = SteamService::getDetails($appId);

        if (empty($details)) {
            $reply = 'Не удалось получить данные игры.';
            $this->sendReply($chatId, $reply);
            return;
        }

        if (empty($details['price'])) {
            $reply = 'Это бесплатная игра. Отслеживание не требуется.';
            $this->sendReply($chatId, $reply);
            return;
        }

        $user = $this->getUser($chatId);

        $gameModel = Game::firstOrCreate(
            ['steam_app_id' => $appId],
            [
                'title' => $details['title'],
                'description' => $details['description'],
                'image_url' => $details['image_url'],
                'genre' => implode(', ', $details['genres']),
                'current_price' => $details['price'],
                'currency' => $details['currency']
            ]
        );

        $user->trackedGames()->firstOrCreate(['game_id' => $gameModel->id]);

        $reply = "{$game['title']} отслеживается.";
        $this->sendReply($chatId, $reply);
    }

    private function handleList(string $chatId): void
    {
        $user = $this->getUser($chatId);

        $trackedGames = $this->getTrackedGames($user);

        if ($trackedGames->isEmpty()) {
            $reply = 'Игры не отслеживаются.';
            $this->sendReply($chatId, $reply);
            return;
        }

        $reply = "Отслеживаемые игры:\n\n";
        foreach ($trackedGames as $i => $t) {
            $num = $i + 1;
            $title = $t->game->title;
            $price = $t->game->current_price ? '$' . number_format($t->game->current_price, 2) : 'N/A';
            $target = $t->target_price ? '$' . number_format($t->target_price, 2) : 'не задано';
            $reply .= "{$num}. {$title}\n Цена: {$price} | Цель: {$target}\n\n";
        }

        $this->sendReply($chatId, $reply);
    }

    private function handlePrice(string $chatId, string $text): void
    {
        $parts = explode(' ', $text);
        $index = (int) ($parts[1] ?? 0) - 1;

        if ($index < 0) {
            $reply = 'Пример использования: /price 1';
            $this->sendReply($chatId, $reply);
            return;
        }

        $user = $this->getUser($chatId);
        $trackedGames = $this->getTrackedGames($user);

        if ($trackedGames->isEmpty()) {
            $reply = 'Игры не отслеживаются.';
            $this->sendReply($chatId, $reply);
            return;
        }

        if (!isset($trackedGames[$index])) {
            $reply = 'Неверный номер. Используй /list чтобы увидеть номера.';
            $this->sendReply($chatId, $reply);
            return;
        }

        $t = $trackedGames[$index];
        $title = $t->game->title;
        $price = $t->game->current_price ? '$' . number_format($t->game->current_price, 2) : 'N/A';
        $target = $t->target_price ? '$' . number_format($t->target_price, 2) : 'не задано';

        $reply = "{$title}\nЦена: {$price}\nЦель: {$target}";
        $this->sendReply($chatId, $reply);
    }

    private function handleSet(string $chatId, string $text): void
    {
        $parts = explode(' ', $text);
        $index = (int) ($parts[1] ?? 0) - 1;
        $targetPrice = (float) ($parts[2] ?? 0);

        if ($index < 0 || $targetPrice <= 0) {
            $reply = 'Пример использования: /set 1 15.99';
            $this->sendReply($chatId, $reply);
            return;
        }

        $user = $this->getUser($chatId);
        $trackedGames = $this->getTrackedGames($user);

        if ($trackedGames->isEmpty()) {
            $reply = 'Игры не отслеживаются.';
            $this->sendReply($chatId, $reply);
            return;
        }

        if (!isset($trackedGames[$index])) {
            $reply = 'Неверный номер. Используй /list чтобы увидеть номера.';
            $this->sendReply($chatId, $reply);
            return;
        }

        $trackedGame = $trackedGames[$index];
        $trackedGame->update(['target_price' => $targetPrice]);

        $reply = "Цель для {$trackedGame->game->title} установлена: \$" . number_format($targetPrice, 2);
        $this->sendReply($chatId, $reply);
    }

    private function handleUntrack(string $chatId, string $text): void
    {
        $parts = explode(' ', $text);
        $index = (int) ($parts[1] ?? 0) - 1;

        if ($index < 0) {
            $reply = 'Пример использования: /untrack 1';
            $this->sendReply($chatId, $reply);
            return;
        }

        $user = $this->getUser($chatId);
        $trackedGames = $this->getTrackedGames($user);

        if ($trackedGames->isEmpty()) {
            $reply = 'Игры не отслеживаются.';
            $this->sendReply($chatId, $reply);
            return;
        }

        if (!isset($trackedGames[$index])) {
            $reply = 'Неверный номер. Используй /list чтобы увидеть номера.';
            $this->sendReply($chatId, $reply);
            return;
        }

        $trackedGame = $trackedGames[$index];
        $trackedGame->delete();

        $reply = "{$trackedGame->game->title} больше не отслеживается.";
        $this->sendReply($chatId, $reply);
    }

    private function handleNotify(string $chatId, string $text): void
    {
        $user = $this->getUser($chatId);

        if (!$user) {
            $this->sendReply($chatId, 'Сначала используй /start');
            return;
        }

        $parts = explode(' ', $text);
        $type = $parts[1] ?? null;

        switch ($type) {
            case 'email':
                $user->update(['notify_email' => !$user->notify_email]);
                $status = $user->fresh()->notify_email ? 'включены' : 'отключены';
                $this->sendReply($chatId, "Email уведомления {$status}.");
                break;
            case 'telegram':
                $user->update(['notify_telegram' => !$user->notify_telegram]);
                $status = $user->fresh()->notify_telegram ? 'включены' : 'отключены';
                $this->sendReply($chatId, "Telegram уведомления {$status}.");
                break;
            default:
                if ($type !== null) {
                    $this->sendReply($chatId, 'Неверный параметр. Используй: /notify email, /notify telegram, /notify');
                } else {
                    $email = $user->notify_email ? 'вкл' : 'откл';
                    $telegram = $user->notify_telegram ? 'вкл' : 'откл';
                    $this->sendReply($chatId, "Email: {$email}\nTelegram: {$telegram}");
                    break;
                }
        }
    }

    private function handleHelp(string $chatId): void
    {
        $reply = "Доступные команды:\n
            /start - приветствие\n
            /email - привязать email\n
            /search - найти игру\n
            /track - отслеживать игру\n
            /list - посмотреть отслеживаемые игры\n
            /price - посмотреть информацию об игре\n
            /set - установить цель по цене\n
            /untrack - прекратить отслеживание\n
            /notify - вкл/откл уведомления\n
            /cancel - отмена текущей команды\n
            /help - показать команды";

        $this->sendReply($chatId, $reply);
    }

    public function handle()
    {
        // 1. Получение обновлений от Telegram
        $token = env('TELEGRAM_BOT_TOKEN');
        $offset = Cache::get('tg_offset', 0);
        $url = "https://api.telegram.org/bot{$token}/getUpdates?offset=" . ($offset + 1);
        $response = Http::get($url);

        // 2. Проверка успешности запроса
        if (!$response->ok()) {
            $this->error('Telegram API error');
            return Command::FAILURE;
        }

        // 3. Если нет новых сообщений — выход
        $updates = $response->json('result');

        if (empty($updates)) {
            $this->info('No new messages');
            return Command::SUCCESS;
        }

        // 4. Обработка каждого сообщения
        foreach ($updates as $update) {
            $message = $update['message'] ?? null;
            if (!$message) continue;

            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';

            if (str_starts_with($text, '/cancel')) {
                $this->handleCancel($chatId);
                continue;
            }

            // 4.1. Проверка состояния пользователя
            $state = Cache::get('tg_state:' . $chatId);

            // Бот ждёт, что пользователь введёт email
            if ($state === 'awaiting_email') {
                Cache::forget('tg_state:' . $chatId);

                if (!filter_var($text, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Неверный формат email. Попробуй /email еще раз.';
                    $this->sendReply($chatId, $message);
                    continue;
                }

                $code = random_int(100000, 999999);

                Cache::put("email_verify:{$code}", [
                    'email' => $text,
                    'chat_id' => $chatId
                ], 600);

                Mail::to($text)->send(new VerificationCodeMail($code));

                Cache::put('tg_state:' . $chatId, 'awaiting_code', 600);

                $reply = 'На твой email отправлен код. Введи его.';
                $this->sendReply($chatId, $reply);

                continue;
            }

            // Бот ждёт код подтверждения
            if ($state === 'awaiting_code') {
                $code = trim($text);
                $data = Cache::get("email_verify:{$code}");

                if (!$data || $data['chat_id'] !== $chatId) {
                    $reply = "Неверный код. Попробуй еще раз.\n\n/cancel для отмены.";
                    $this->sendReply($chatId, $reply);
                    continue;
                }

                Cache::forget('tg_state:' . $chatId);
                Cache::forget("email_verify:{$code}");

                $email = $data['email'];
                $user = $this->getUser($chatId);

                $user->update(['email' => $email, 'telegram_id' => $chatId]);

                $reply = 'Почта привязана.';
                $this->sendReply($chatId, $reply);

                continue;
            }

            // Бот ждёт название игры для поиска
            if ($state === 'awaiting_search') {
                Cache::forget('tg_state:' . $chatId);

                $results = SteamService::search($text);

                if (empty($results)) {
                    $this->sendReply($chatId, 'Ничего не найдено.');
                    continue;
                }

                $results = array_slice($results, 0, 10);
                Cache::put('tg_search:' . $chatId, $results, 300);

                $reply = '';
                foreach ($results as $i => $game) {
                    $num = $i + 1;
                    $reply .= "{$num}. {$game['title']}\n";
                }
                $reply .= "\nВведи /track 1 чтобы добавить в отслеживание.";

                $this->sendReply($chatId, $reply);

                continue;
            }

            // 4.2. Обработка команд
            if (str_starts_with($text, '/start')) {
                $this->handleStart($chatId, $message);
            } elseif (str_starts_with($text, '/email')) {
                $this->handleEmail($chatId);
            } elseif (str_starts_with($text, '/search')) {
                $this->handleSearch($chatId);
            } elseif (str_starts_with($text, '/track')) {
                $this->handleTrack($chatId, $text);
            } elseif (str_starts_with($text, '/list')) {
                $this->handleList($chatId);
            } elseif (str_starts_with($text, '/price')) {
                $this->handlePrice($chatId, $text);
            } elseif (str_starts_with($text, '/set')) {
                $this->handleSet($chatId, $text);
            } elseif (str_starts_with($text, '/untrack')) {
                $this->handleUntrack($chatId, $text);
            } elseif (str_starts_with($text, '/notify')) {
                $this->handleNotify($chatId, $text);
            } elseif (str_starts_with($text, '/help')) {
                $this->handleHelp($chatId);
            }

            $this->info("Chat: {$chatId} | Message: {$text}");
        }

        // 5. Сохранение последнего обработанного update_id
        if (!empty($updates)) {
            $lastUpdate = end($updates);
            Cache::put('tg_offset', $lastUpdate['update_id'], 86400);
        }

        return Command::SUCCESS;
    }
}
