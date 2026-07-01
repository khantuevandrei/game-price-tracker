<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'steam_app_id', 'description', 'image_url', 'genre', 'current_price', 'currency'])]
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
