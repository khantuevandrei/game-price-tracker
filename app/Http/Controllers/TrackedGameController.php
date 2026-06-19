<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\TrackedGame;
use App\Services\SteamService;
use Illuminate\Http\Request;

class TrackedGameController extends Controller
{
    public function store(string $appId)
    {
        $details = SteamService::getDetails($appId);

        if (empty($details)) abort(404);

        if (empty($details['price'])) {
            return back()->with('error', 'This game is free. No need for tracking');
        }

        $game = Game::firstOrCreate(
            ['steam_app_id' => $appId],
            [
                'title' => $details['title'],
                'description' => $details['description'],
                'image_url' => $details['image_url'],
                'genre' => implode(', ', $details['genres']),
                'current_price' => $details['price'],
                'currency' => $details['currency']
            ]
        );

        auth()->user()->trackedGames()->firstOrCreate(['game_id' => $game->id]);

        return back()->with('success', 'Game is now being tracked');
    }

    public function edit(TrackedGame $trackedGame)
    {
        if ($trackedGame->user->id !== auth()->id()) abort(403);

        return view('tracked.edit', ['trackedGame' => $trackedGame]);
    }

    public function update(Request $request, TrackedGame $trackedGame)
    {
        if ($trackedGame->user->id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'target_price' => 'numeric|nullable',
        ]);

        $validated['notify_email'] = $request->has('notify_email');
        $validated['notify_telegram'] = $request->has('notify_telegram');

        $trackedGame->update($validated);

        return redirect('/dashboard')->with('success', 'Tracked game was updated');
    }

    public function destroy(TrackedGame $trackedGame)
    {
        if ($trackedGame->user_id !== auth()->id()) abort(403);

        $trackedGame->delete();

        return back()->with('success', 'Game removed from tracking');
    }
}
