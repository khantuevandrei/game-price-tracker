<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\SteamService;
use Illuminate\Http\Request;

class TrackedGameController extends Controller
{
    public function store(string $appId)
    {
        $details = SteamService::getDetails($appId);

        if (empty($details)) abort(404);

        $game = Game::firstOrCreate(
            ['steam_app_id' => $appId],
            [
                'title' => $details['title'],
                'description' => $details['description'],
                'image_url' => $details['image_url'],
                'genre' => implode(', ', $details['genres']),
                'current_price' => $details['price'],
            ]
        );

        auth()->user()->trackedGames()->firstOrCreate(['game_id' => $game->id]);

        return back()->with('success', 'Game is now being tracked');
    }
}
