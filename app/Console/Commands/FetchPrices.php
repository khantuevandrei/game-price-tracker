<?php

namespace App\Console\Commands;

use App\Jobs\SendPriceAlert;
use App\Models\Game;
use App\Models\PriceHistory;
use App\Models\TrackedGame;
use App\Services\SteamService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('prices:fetch')]
#[Description('Command description')]
class FetchPrices extends Command
{
    public function handle()
    {
        $gameIds = TrackedGame::query()->distinct()->pluck('game_id');

        $games = Game::whereIn('id', $gameIds)->get();

        foreach ($games as $game) {
            $details = SteamService::getDetails($game->steam_app_id);

            if (!$details || !isset($details['price'])) continue;

            $newPrice = $details['price'] / 100;
            $oldPrice = $game->current_price;

            if ($oldPrice !== $newPrice) {
                PriceHistory::create([
                    'game_id' => $game->id,
                    'price' => $newPrice,
                    'currency' => $details['currency'] ?? 'USD',
                    'recorded_at' => now()
                ]);

                $game->update([
                    'current_price' => $newPrice
                ]);

                $trackedGames = TrackedGame::where('game_id', $game->id)
                    ->whereNotNull('target_price')
                    ->where('target_price', '>', $newPrice)
                    ->get();

                foreach ($trackedGames as $trackedGame) {
                    SendPriceAlert::dispatchSync($trackedGame, $newPrice);
                    $this->info("Dispatched alert for {$trackedGame->user->email}");
                }

                $this->info("Updated {$game->title} - USD {$newPrice}");
            } else {
                $this->info("No change for {$game->title}");
            }
        }

        return Command::SUCCESS;
    }
}
