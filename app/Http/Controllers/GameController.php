<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\SteamService;

class GameController extends Controller
{
    public function index()
    {
        $query = request('q');
        $result = [];

        if ($query) {
            $result = SteamService::search($query);
        }

        return view('games.index', ['results' => $result]);
    }

    public function show(string $appId)
    {
        $result = SteamService::getDetails((int) $appId);
        $isTracked = auth()->check() && auth()->user()->trackedGames()->whereHas('game', fn ($q) => $q->where('steam_app_id', $appId))->exists();
        $trackedGame = auth()->check()
            ? auth()->user()->trackedGames()->whereHas('game', fn ($q) => $q->where('steam_app_id', $appId))->first()
            : null;

        if (empty($result)) {
            abort(404);
        }

        $gameModel = Game::where('steam_app_id', $appId)->first();
        $priceHistory = $gameModel
            ? $gameModel->priceHistory()->orderBy('recorded_at')->get()
            : collect();

        return view('games.show', [
            'game' => $result,
            'isTracked' => $isTracked,
            'trackedGame' => $trackedGame,
            'priceHistory' => $priceHistory,
        ]);
    }
}
