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
        if ($this->trackedGame->user->notify_email) {
            Mail::to($this->trackedGame->user->email)->send(new PriceDroppedMail($this->trackedGame, $this->newPrice));
        }

        if ($this->trackedGame->user->notify_telegram && $this->trackedGame->user->telegram_id) {
            $token = env('TELEGRAM_BOT_TOKEN');
            $chatId = $this->trackedGame->user->telegram_id;
            $text = "Цена на {$this->trackedGame->game->title} упала до \$"
                .number_format($this->newPrice, 2)
                .".\nТвоя цель: \$"
                .number_format($this->trackedGame->target_price, 2);

            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        }
    }
}
