<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['game_id', 'price', 'currency', 'recorded_at'])]
class PriceHistory extends Model
{
    protected $table = 'price_history';

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
