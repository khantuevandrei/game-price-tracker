<?php

namespace App\Jobs;

use App\Mail\PriceDroppedMail;
use App\Models\TrackedGame;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SendPriceAlert implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public TrackedGame $trackedGame, public int $newPrice) {}

    public function handle(): void
    {
        $user = $this->trackedGame->user;
        $locale = $user->locale ?? config('app.fallback_locale', 'en');

        $params = [
            'game'   => $this->trackedGame->game->title,
            'price'  => number_format($this->newPrice, 2),
            'target' => number_format($this->trackedGame->target_price, 2),
        ];

        if ($user->notify_email) {
            Mail::to($user->email)
                ->locale($locale)
                ->send(new PriceDroppedMail($this->trackedGame, $this->newPrice));
        }

        if ($user->notify_telegram && $user->telegram_id) {
            Http::post('https://api.telegram.org/bot' . config('services.telegram.token') . '/sendMessage', [
                'chat_id' => $user->telegram_id,
                'text'    => __('alerts.price_dropped', $params, $locale),
            ]);
        }
    }
}
