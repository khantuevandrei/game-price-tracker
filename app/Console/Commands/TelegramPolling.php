<?php

namespace App\Console\Commands;

use App\Mail\VerificationCodeMail;
use App\Models\Game;
use App\Models\User;
use App\Services\SteamService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

#[Signature('app:telegram-polling')]
#[Description('Poll Telegram Bot API for new messages and dispatch bot commands')]
class TelegramPolling extends Command
{
    private function sendReply(string $chatId, string $reply): void
    {
        $token = env('TELEGRAM_BOT_TOKEN');

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $reply,
        ]);
    }

    private function applyLocale(array $message): void
    {
        $lang = $message['from']['language_code'] ?? 'en';
        $lang = substr($lang, 0, 2);
        app()->setLocale(in_array($lang, ['en', 'ru'], true) ? $lang : 'en');
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
        $this->sendReply($chatId, __('telegram.cancelled'));
    }

    private function handleStart(string $chatId, array $message): void
    {
        $user = $this->getUser($chatId);

        if (! $user) {
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

        $this->sendReply($chatId, __('telegram.start'));
    }

    private function handleEmail(string $chatId): void
    {
        $user = $this->getUser($chatId);
        $currentEmail = $user->email ?? null;

        Cache::put('tg_state:' . $chatId, 'awaiting_email', 300);

        if ($currentEmail && ! str_starts_with($currentEmail, 'tg_')) {
            $reply = __('telegram.email_current', ['email' => $currentEmail]);
        } else {
            $reply = __('telegram.email_prompt');
        }

        $this->sendReply($chatId, $reply);
    }

    private function handleSearch(string $chatId): void
    {
        Cache::put('tg_state:' . $chatId, 'awaiting_search', 300);
        $this->sendReply($chatId, __('telegram.search_prompt'));
    }

    private function handleTrack(string $chatId, string $text): void
    {
        $parts = explode(' ', $text);
        $index = (int) ($parts[1] ?? 0) - 1;

        if ($index < 0) {
            $this->sendReply($chatId, __('telegram.track_usage'));

            return;
        }

        $results = Cache::get('tg_search:' . $chatId);

        if (! $results || ! isset($results[$index])) {
            $this->sendReply($chatId, __('telegram.track_no_search'));

            return;
        }

        $game = $results[$index];
        $appId = $game['id'];

        $details = SteamService::getDetails($appId);

        if (empty($details)) {
            $this->sendReply($chatId, __('telegram.track_no_details'));

            return;
        }

        if (empty($details['price'])) {
            $this->sendReply($chatId, __('telegram.track_free'));

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
                'current_price' => $details['price'] / 100,
                'currency' => $details['currency'],
            ]
        );

        $user->trackedGames()->firstOrCreate(['game_id' => $gameModel->id]);

        $this->sendReply($chatId, __('telegram.track_success', ['title' => $game['title']]));
    }

    private function handleList(string $chatId): void
    {
        $user = $this->getUser($chatId);
        $trackedGames = $this->getTrackedGames($user);

        if ($trackedGames->isEmpty()) {
            $this->sendReply($chatId, __('telegram.list_empty'));

            return;
        }

        $reply = __('telegram.list_header');
        foreach ($trackedGames as $i => $t) {
            $reply .= __('telegram.list_item', [
                'num' => $i + 1,
                'title' => $t->game->title,
                'price' => $t->game->current_price ? '$' . number_format($t->game->current_price, 2) : 'N/A',
                'target' => $t->target_price ? '$' . number_format($t->target_price, 2) : __('telegram.target_unset'),
            ]);
        }

        $this->sendReply($chatId, $reply);
    }

    private function handlePrice(string $chatId, string $text): void
    {
        $parts = explode(' ', $text);
        $index = (int) ($parts[1] ?? 0) - 1;

        if ($index < 0) {
            $this->sendReply($chatId, __('telegram.price_usage'));

            return;
        }

        $user = $this->getUser($chatId);
        $trackedGames = $this->getTrackedGames($user);

        if ($trackedGames->isEmpty()) {
            $this->sendReply($chatId, __('telegram.list_empty'));

            return;
        }

        if (! isset($trackedGames[$index])) {
            $this->sendReply($chatId, __('telegram.invalid_index'));

            return;
        }

        $t = $trackedGames[$index];

        $this->sendReply($chatId, __('telegram.price_result', [
            'title' => $t->game->title,
            'price' => $t->game->current_price ? '$' . number_format($t->game->current_price, 2) : 'N/A',
            'target' => $t->target_price ? '$' . number_format($t->target_price, 2) : __('telegram.target_unset'),
        ]));
    }

    private function handleSet(string $chatId, string $text): void
    {
        $parts = explode(' ', $text);
        $index = (int) ($parts[1] ?? 0) - 1;
        $targetPrice = (float) ($parts[2] ?? 0);

        if ($index < 0 || $targetPrice <= 0) {
            $this->sendReply($chatId, __('telegram.set_usage'));

            return;
        }

        $user = $this->getUser($chatId);
        $trackedGames = $this->getTrackedGames($user);

        if ($trackedGames->isEmpty()) {
            $this->sendReply($chatId, __('telegram.list_empty'));

            return;
        }

        if (! isset($trackedGames[$index])) {
            $this->sendReply($chatId, __('telegram.invalid_index'));

            return;
        }

        $trackedGame = $trackedGames[$index];
        $trackedGame->update(['target_price' => $targetPrice]);

        $this->sendReply($chatId, __('telegram.set_success', [
            'title' => $trackedGame->game->title,
            'price' => number_format($targetPrice, 2),
        ]));
    }

    private function handleUntrack(string $chatId, string $text): void
    {
        $parts = explode(' ', $text);
        $index = (int) ($parts[1] ?? 0) - 1;

        if ($index < 0) {
            $this->sendReply($chatId, __('telegram.untrack_usage'));

            return;
        }

        $user = $this->getUser($chatId);
        $trackedGames = $this->getTrackedGames($user);

        if ($trackedGames->isEmpty()) {
            $this->sendReply($chatId, __('telegram.list_empty'));

            return;
        }

        if (! isset($trackedGames[$index])) {
            $this->sendReply($chatId, __('telegram.invalid_index'));

            return;
        }

        $trackedGame = $trackedGames[$index];
        $title = $trackedGame->game->title;
        $trackedGame->delete();

        $this->sendReply($chatId, __('telegram.untrack_success', ['title' => $title]));
    }

    private function handleNotify(string $chatId, string $text): void
    {
        $user = $this->getUser($chatId);

        if (! $user) {
            $this->sendReply($chatId, __('telegram.notify_need_start'));

            return;
        }

        $parts = explode(' ', $text);
        $type = $parts[1] ?? null;

        switch ($type) {
            case 'email':
                $user->update(['notify_email' => ! $user->notify_email]);
                $status = $user->fresh()->notify_email ? __('telegram.notify_enabled') : __('telegram.notify_disabled');
                $this->sendReply($chatId, __('telegram.notify_email_toggled', ['status' => $status]));
                break;
            case 'telegram':
                $user->update(['notify_telegram' => ! $user->notify_telegram]);
                $status = $user->fresh()->notify_telegram ? __('telegram.notify_enabled') : __('telegram.notify_disabled');
                $this->sendReply($chatId, __('telegram.notify_telegram_toggled', ['status' => $status]));
                break;
            default:
                if ($type !== null) {
                    $this->sendReply($chatId, __('telegram.notify_invalid_param'));
                } else {
                    $email = $user->notify_email ? __('telegram.notify_on') : __('telegram.notify_off');
                    $telegram = $user->notify_telegram ? __('telegram.notify_on') : __('telegram.notify_off');
                    $this->sendReply($chatId, __('telegram.notify_status', ['email' => $email, 'telegram' => $telegram]));
                }
        }
    }

    private function handleHelp(string $chatId): void
    {
        $this->sendReply($chatId, __('telegram.help'));
    }

    private function handleLink(string $chatId, string $text): void
    {
        $parts = explode(' ', trim($text));
        $code = strtoupper($parts[1] ?? '');

        if (! $code) {
            $this->sendReply($chatId, __('telegram.link_usage'));

            return;
        }

        $userId = Cache::get('tg_link:' . $code);

        if (! $userId) {
            $this->sendReply($chatId, __('telegram.link_invalid'));

            return;
        }

        $webUser = User::find($userId);
        if (! $webUser) {
            $this->sendReply($chatId, __('telegram.link_invalid'));

            return;
        }

        Cache::forget('tg_link:' . $code);

        $phantom = User::where('telegram_id', $chatId)
            ->where('id', '!=', $webUser->id)
            ->first();

        $merged = false;
        if ($phantom) {
            $phantom->trackedGames()->update(['user_id' => $webUser->id]);
            $phantom->delete();
            $merged = true;
        }

        $webUser->update(['telegram_id' => $chatId]);

        $key = $merged ? 'telegram.link_merged' : 'telegram.link_success';
        $this->sendReply($chatId, __($key, ['email' => $webUser->email]));
    }

    private function handleUnlink(string $chatId): void
    {
        $user = $this->getUser($chatId);

        if (! $user) {
            $this->sendReply($chatId, __('telegram.unlink_not_linked'));

            return;
        }

        $email = $user->email;
        $user->update(['telegram_id' => null]);

        $this->sendReply($chatId, __('telegram.unlink_success', ['email' => $email]));
    }

    public function handle()
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $offset = Cache::get('tg_offset', 0);
        $url = "https://api.telegram.org/bot{$token}/getUpdates?offset=" . ($offset + 1);
        $response = Http::get($url);

        if (! $response->ok()) {
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
            if (! $message) {
                continue;
            }

            $this->applyLocale($message);

            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';

            if (str_starts_with($text, '/cancel')) {
                $this->handleCancel($chatId);

                continue;
            }

            $state = Cache::get('tg_state:' . $chatId);

            if ($state === 'awaiting_email') {
                Cache::forget('tg_state:' . $chatId);

                if (! filter_var($text, FILTER_VALIDATE_EMAIL)) {
                    $this->sendReply($chatId, __('telegram.email_invalid'));

                    continue;
                }

                $code = random_int(100000, 999999);

                Cache::put("email_verify:{$code}", [
                    'email' => $text,
                    'chat_id' => $chatId,
                ], 600);

                Mail::to($text)->send(new VerificationCodeMail($code));

                Cache::put('tg_state:' . $chatId, 'awaiting_code', 600);

                $this->sendReply($chatId, __('telegram.email_code_sent'));

                continue;
            }

            if ($state === 'awaiting_code') {
                $code = trim($text);
                $data = Cache::get("email_verify:{$code}");

                if (! $data || $data['chat_id'] !== $chatId) {
                    $this->sendReply($chatId, __('telegram.email_code_invalid'));

                    continue;
                }

                Cache::forget('tg_state:' . $chatId);
                Cache::forget("email_verify:{$code}");

                $email = $data['email'];
                $user = $this->getUser($chatId);

                $user->update(['email' => $email, 'telegram_id' => $chatId]);

                $password = substr(bin2hex(random_bytes(4)), 0, 8);
                $user->update(['password' => bcrypt($password)]);

                $this->sendReply($chatId, __('telegram.email_linked', ['password' => $password]));

                continue;
            }

            if ($state === 'awaiting_search') {
                Cache::forget('tg_state:' . $chatId);

                $results = SteamService::search($text);

                if (empty($results)) {
                    $this->sendReply($chatId, __('telegram.search_no_results'));

                    continue;
                }

                $results = array_slice($results, 0, 10);
                Cache::put('tg_search:' . $chatId, $results, 300);

                $reply = '';
                foreach ($results as $i => $game) {
                    $reply .= __('telegram.search_result_item', ['num' => $i + 1, 'title' => $game['title']]);
                }
                $reply .= __('telegram.search_footer');

                $this->sendReply($chatId, $reply);

                continue;
            }

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
            } elseif (str_starts_with($text, '/link')) {
                $this->handleLink($chatId, $text);
            } elseif (str_starts_with($text, '/unlink')) {
                $this->handleUnlink($chatId);
            }
            $this->info("Chat: {$chatId} | Message: {$text}");
        }

        if (! empty($updates)) {
            $lastUpdate = end($updates);
            Cache::put('tg_offset', $lastUpdate['update_id'], 86400);
        }

        return Command::SUCCESS;
    }
}
