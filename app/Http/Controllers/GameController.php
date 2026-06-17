<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SteamService;

class GameController extends Controller
{
    public function index()
    {
        $query = request('q');
        $result = [];

        if ($query) $result = SteamService::search($query);

        return view('games.index', ['results' => $result]);
    }
}
