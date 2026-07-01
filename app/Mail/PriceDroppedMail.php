<?php

namespace App\Mail;

use App\Models\TrackedGame;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PriceDroppedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TrackedGame $trackedGame, public int $newPrice) {}

    public function build(): self
    {
        return $this->subject('Price dropped: '.$this->trackedGame->game->title)->view('emails.price-dropped');
    }
}
