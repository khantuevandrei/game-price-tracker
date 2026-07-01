<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GameTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_game(): void
    {
        Http::fake([
            'store.steampowered.com/search/*' => Http::response(
                '<a data-ds-appid="1245620"><span class="title">Elden Ring</span></a>',
                200
            ),
        ]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/?q=Elden');

        $response->assertStatus(200);
        $response->assertSee('Elden Ring');
    }

    public function test_user_can_track_game(): void
    {
        Http::fake([
            'store.steampowered.com/api/appdetails*' => Http::response([
                '1245620' => [
                    'success' => true,
                    'data' => [
                        'steam_appid' => 1245620,
                        'name' => 'Elden Ring',
                        'short_description' => 'Rise, Tarnished',
                        'genres' => [['description' => 'Action']],
                        'price_overview' => ['final' => 3999, 'currency' => 'USD'],
                        'header_image' => 'https://example.com/image.jpg',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->post('/games/1245620/track');

        $response->assertRedirect();
        $this->assertDatabaseHas('games', [
            'steam_app_id' => '1245620',
            'title' => 'Elden Ring',
        ]);
        $this->assertDatabaseHas('tracked_games', [
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_see_dashboard(): void
    {
        $user = User::factory()->create();
        $game = Game::create([
            'steam_app_id' => '12345620',
            'title' => 'Elden Ring',
            'description' => 'Rise, Tarnished',
            'image_url' => 'https://example.com/image.jpg',
            'genre' => 'Action, RPG',
            'current_price' => 39.99,
            'currency' => 'USD',
        ]);

        $user->trackedGames()->create([
            'game_id' => $game->id,
            'target_price' => 29.99,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Elden Ring');
        $response->assertSee('29.99');
    }

    public function test_user_can_untrack_game(): void
    {
        $user = User::factory()->create();
        $game = Game::create([
            'steam_app_id' => '1245620',
            'title' => 'Elden Ring',
            'description' => 'Rise, Tarnished',
            'image_url' => 'https://example.com/image.jpg',
            'genre' => 'Action, RPG',
            'current_price' => 39.99,
            'currency' => 'USD',
        ]);

        $tracked = $user->trackedGames()->create(['game_id' => $game->id]);

        $response = $this->actingAs($user)->delete("/tracked/{$tracked->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('tracked_games', ['id' => $tracked->id]);
    }
}
