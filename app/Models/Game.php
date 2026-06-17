<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    public function trackedGame()
    {
        return $this->hasMany(TrackedGame::class);
    }

    public function priceHistory()
    {
        return $this->hasMany(PriceHistory::class);
    }
}
