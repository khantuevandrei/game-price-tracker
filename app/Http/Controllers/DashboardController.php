<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        $trackedGames = auth()->user()->trackedGames()->with('game')->get();

        return view('dashboard.index', ['trackedGames' => $trackedGames]);
    }
}
