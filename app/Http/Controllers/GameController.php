<?php

namespace App\Http\Controllers;

use App\Services\SteamService;

use function PHPUnit\Framework\isEmpty;

class GameController extends Controller
{
    public function index()
    {
        $query = request('q');
        $result = [];

        if ($query) $result = SteamService::search($query);

        return view('games.index', ['results' => $result]);
    }

    public function show(string $appId)
    {
        $result = SteamService::getDetails((int) $appId);

        if (empty($result)) abort(404);

        return view('games.show', ['game' => $result]);
    }
}
