<?php

namespace App\Jobs;

use App\Mail\PriceDroppedMail;
use App\Models\TrackedGame;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPriceAlert implements ShouldQueue
{
    use Queueable;

    public function __construct(public TrackedGame $trackedGame, public int $newPrice) {}

    public function handle(): void
    {
        Mail::to($this->trackedGame->user->email)->send(new PriceDroppedMail($this->trackedGame, $this->newPrice));
    }
}
