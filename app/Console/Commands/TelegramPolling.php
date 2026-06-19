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
    public function handle()
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $offset = Cache::get('tg_offset', 0);
        $url = "https://api.telegram.org/bot{$token}/getUpdates?offset=" . ($offset + 1);
        $replyUrl = "https://api.telegram.org/bot{$token}/sendMessage";

        $response = Http::get($url);

        if (!$response->ok()) {
            $this->error('Telegram API error');
            return Command::FAILURE;
        }

        $updates = $response->json('result');

        if (empty($updates)) {
            $this->info('No new messages');
            return Command::SUCCESS;
        }

        foreach ($updates as $update) {
            $message = $update['message'] ?? null;
            if (!$message) continue;

            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';

            $state = Cache::get('tg_state:' . $chatId);

            if ($state === 'awaiting_email') {
                Cache::forget('tg_state:' . $chatId);

                // Валидация email
                if (!filter_var($text, FILTER_VALIDATE_EMAIL)) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Неверный формат email. Попробуй /email еще раз.'
                    ]);
                    continue;
                }

                // Генерация кода
                $code = random_int(100000, 999999);

                // Сохраняем в кеш
                Cache::put("email_verify:{$code}", [
                    'email' => $text,
                    'chat_id' => $chatId
                ], 600);

                // Отправляем письмо
                Mail::to($text)->send(new VerificationCodeMail($code));

                // Меняем состояние
                Cache::put('tg_state:' . $chatId, 'awaiting_code', 600);

                Http::post($replyUrl, [
                    'chat_id' => $chatId,
                    'text' => 'На твой email отправлен код. Введи его.'
                ]);

                continue;
            }

            if ($state === 'awaiting_code') {
                $code = trim($text);
                $data = Cache::get("email_verify:{$code}");

                if (!$data || $data['chat_id'] !== $chatId) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Неверный код. Попробуй еще раз или начни заново с /email'
                    ]);
                    continue;
                }

                Cache::forget('tg_state:' . $chatId);
                Cache::forget("email_verify:{$code}");

                $email = $data['email'];
                $user = User::where('email', $email)->first();

                if (!$user) {
                    $password = substr(bin2hex(random_bytes(4)), 0, 8);
                    $firstName = $message['chat']['first_name'] ?? '';
                    $lastName = $message['chat']['last_name'] ?? '';
                    $name = trim($firstName . ' ' . $lastName) ?: 'Telegram User';

                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => bcrypt($password),
                        'telegram_id' => $chatId
                    ]);

                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => "Почта привязана. Для доступа на сайт используй пароль: {$password}"
                    ]);
                } else {
                    $user->update(['telegram_id' => $chatId]);
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Почта привязана.'
                    ]);
                }

                continue;
            }

            if ($state === 'awaiting_search') {
                Cache::forget('tg_state:' . $chatId);

                $results = SteamService::search($text);

                if (empty($results)) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Ничего не найдено.'
                    ]);
                    continue;
                }

                $results = array_slice($results, 0, 5);
                Cache::put('tg_search:' . $chatId, $results, 300);

                $response = '';
                foreach ($results as $i => $game) {
                    $num = $i + 1;
                    $response .= "{$num}. {$game['title']}\n";
                }
                $response .= "\nВведи /track 1 чтобы добавить в отслеживание.";

                Http::post($replyUrl, [
                    'chat_id' => $chatId,
                    'text' => $response
                ]);
                continue;
            }

            if (str_starts_with($text, '/start')) {
                $user = User::where('telegram_id', $chatId)->first();

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

                Http::post($replyUrl, [
                    'chat_id' => $chatId,
                    'text' => "Привет! Это бот для отслеживания цен на игры в Steam.\n\n
                    Доступные команды:\n
                    /start - приветствие\n
                    /email - привязать email\n
                    /search - найти игру\n
                    /track - отслеживать игру\n
                    /list - посмотреть отслеживаемые игры\n
                    /price - посмотреть информацию об игре\n
                    /set - установить цель по цене\n
                    /untrack - прекратить отслеживание\n
                    /help - напомнить команды"
                ]);
            } elseif (str_starts_with($text, '/email')) {
                Cache::put('tg_state:' . $chatId, 'awaiting_email', 300);
                Http::post($replyUrl, [
                    'chat_id' => $chatId,
                    'text' => 'Введи свой email.'
                ]);
            } elseif (str_starts_with($text, '/search')) {
                Cache::put('tg_state:' . $chatId, 'awaiting_search', 300);
                Http::post($replyUrl, [
                    'chat_id' => $chatId,
                    'text' => 'Введи название игры.'
                ]);
            } elseif (str_starts_with($text, '/track')) {
                $parts = explode(' ', $text);
                $index = (int) ($parts[1] ?? 0) - 1;

                if ($index < 0) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Использование: /track 1'
                    ]);
                    continue;
                }

                $results = Cache::get('tg_search:' . $chatId);

                if (!$results || !isset($results[$index])) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Сначала выполни поиск через /search'
                    ]);
                    continue;
                }

                $game = $results[$index];
                $appId = $game['id'];

                $details = SteamService::getDetails($appId);

                if (empty($details)) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Не удалось получить данные игры.'
                    ]);
                    continue;
                }

                if (empty($details['price'])) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Это бесплатная игра. Отслеживание не требуется.'
                    ]);
                    continue;
                }

                $user = User::where('telegram_id', $chatId)->first();

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

                Http::post($replyUrl, [
                    'chat_id' => $chatId,
                    'text' => "{$game['title']} добавлено в отслеживание."
                ]);
            } elseif (str_starts_with($text, '/list')) {
                $user = User::where('telegram_id', $chatId)->first();

                $trackedGames = $user->trackedGames()->with('game')->get();

                if ($trackedGames->isEmpty()) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Ты не отслеживаешь ни одну игру.'
                    ]);
                    continue;
                }

                $response = "Отслеживаемые игры:\n\n";
                foreach ($trackedGames as $i => $t) {
                    $num = $i + 1;
                    $title = $t->game->title;
                    $price = $t->game->current_price ? '$' . number_format($t->game->current_price, 2) : 'N/A';
                    $target = $t->target_price ? '$' . number_format($t->target_price, 2) : 'не задано';
                    $response .= "{$num}. {$title}\n Цена: {$price} | Цель: {$target}\n\n";
                }

                Http::post($replyUrl, [
                    'chat_id' => $chatId,
                    'text' => $response
                ]);
            } elseif (str_starts_with($text, '/price')) {
                $parts = explode(' ', $text);
                $index = (int) ($parts[1] ?? 0) - 1;

                if ($index < 0) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Пример использования: /price 1'
                    ]);
                    continue;
                }

                $user = User::where('telegram_id', $chatId)->first();
                $trackedGames = $user->trackedGames()->with('game')->get();

                if ($trackedGames->isEmpty()) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Ты не отслеживаешь ни одну игру.'
                    ]);
                    continue;
                }

                if (!isset($trackedGames[$index])) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Неверный номер. Используй /list чтобы увидеть номера.'
                    ]);
                    continue;
                }

                $t = $trackedGames[$index];
                $title = $t->game->title;
                $price = $t->game->current_price ? '$' . number_format($t->game->current_price, 2) : 'N/A';
                $target = $t->target_price ? '$' . number_format($t->target_price, 2) : 'не задано';

                Http::post($replyUrl, [
                    'chat_id' => $chatId,
                    'text' => "{$title}\nЦена: {$price}\nЦель: {$target}"
                ]);
            } elseif (str_starts_with($text, '/set')) {
                $parts = explode(' ', $text);
                $index = (int) ($parts[1] ?? 0) - 1;
                $targetPrice = (float) ($parts[2] ?? 0);

                if ($index < 0 || $targetPrice <= 0) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Пример использования: /set 1 15.99'
                    ]);
                    continue;
                }

                $user = User::where('telegram_id', $chatId)->first();
                $trackedGames = $user->trackedGames()->with('game')->get();

                if (!isset($trackedGames[$index])) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Неверный номер. Используй /list чтобы увидеть номера.'
                    ]);
                    continue;
                }

                $trackedGame = $trackedGames[$index];
                $trackedGame->update(['target_price' => $targetPrice]);

                Http::post($replyUrl, [
                    'chat_id' => $chatId,
                    'text' => "Цель для {$trackedGame->game->title} установлена: \$" . number_format($targetPrice, 2)
                ]);
            } elseif (str_starts_with($text, '/untrack')) {
                $parts = explode(' ', $text);
                $index = (int) ($parts[1] ?? 0) - 1;

                if ($index < 0) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Пример использования: /untrack 1'
                    ]);
                    continue;
                }

                $user = User::where('telegram_id', $chatId)->first();
                $trackedGames = $user->trackedGames()->with('game')->get();

                if ($trackedGames->isEmpty()) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Ты не отслеживаешь ни одну игру.'
                    ]);
                    continue;
                }

                if (!isset($trackedGames[$index])) {
                    Http::post($replyUrl, [
                        'chat_id' => $chatId,
                        'text' => 'Неверный номер. Используй /list чтобы увидеть номера.'
                    ]);
                    continue;
                }

                $trackedGame = $trackedGames[$index];
                $trackedGame->delete();

                Http::post($replyUrl, [
                    'chat_id' => $chatId,
                    'text' => "{$trackedGame->game->title} удалена из отслеживания."
                ]);
            } elseif (str_starts_with($text, '/help')) {
                Http::post($replyUrl, [
                    'chat_id' => $chatId,
                    'text' => "Доступные команды:\n
                    /start - приветствие\n
                    /email - привязать email\n
                    /search - найти игру\n
                    /track - отслеживать игру\n
                    /list - посмотреть отслеживаемые игры\n
                    /price - посмотреть информацию об игре\n
                    /set - установить цель по цене\n
                    /untrack - прекратить отслеживание\n
                    /help - напомнить команды"
                ]);
            }

            $this->info("Chat: {$chatId} | Message: {$text}");
        }

        if (!empty($updates)) {
            $lastUpdate = end($updates);
            Cache::put('tg_offset', $lastUpdate['update_id'], 86400);
        }

        return Command::SUCCESS;
    }
}
