<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['title', 'steam_app_id', 'description', 'image_url', 'genre', 'current_price'])]
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
