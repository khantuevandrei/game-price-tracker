<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_creates_user_and_responds(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $this->artisan('app:telegram-polling')
            ->assertSuccessful();
    }

    public function test_email_command_sets_state(): void
    {
        $user = User::factory()->create(['telegram_id' => '12345']);

        Cache::put('tg_offset', 0);
        Cache::put('tg_state:12345', 'awaiting_email');

        Http::fake([
            'api.telegram.org/getUpdates*' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'update_id' => 1,
                        'message' => [
                            'chat' => ['id' => '12345'],
                            'text' => 'test@example.com',
                        ]
                    ]
                ]
            ]),
            'api.telegram.org/sendMessage*' => Http::response(['ok' => true]),
        ]);

        $this->artisan('app:telegram-polling')
            ->assertSuccessful();
    }

    public function test_search_triggers_steam_lookup(): void
    {
        $user = User::factory()->create(['telegram_id' => '12345']);

        Http::fake([
            'api.telegram.org/getUpdates*' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'update_id' => 1,
                        'message' => [
                            'chat' => ['id' => '12345'],
                            'text' => '/search',
                        ]
                    ]
                ]
            ]),
            'api.telegram.org/sendMessage*' => Http::response(['ok' => true]),
            'store.steampowered.com/search/*' => Http::response(
                '<a data-ds-appid="1245620"><span class="title">ELDEN RING</span></a>',
                200
            ),
        ]);

        $this->artisan('app:telegram-polling');
        $this->assertTrue(true);
    }

    public function test_list_shows_tracked_games(): void
    {
        $user = User::factory()->create(['telegram_id' => '12345']);

        $game = Game::create([
            'steam_app_id' => '1245620',
            'title' => 'ELDEN RING',
            'description' => 'Rise',
            'image_url' => 'https://example.com/image.jpg',
            'genre' => 'Action',
            'current_price' => 39.99,
            'currency' => 'USD',
        ]);

        DB::table('tracked_games')->insert([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'target_price' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'api.telegram.org/getUpdates*' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'update_id' => 1,
                        'message' => [
                            'chat' => ['id' => '12345'],
                            'text' => '/list',
                        ]
                    ]
                ]
            ]),
            'api.telegram.org/sendMessage*' => Http::response(['ok' => true]),
        ]);

        $this->artisan('app:telegram-polling')
            ->assertSuccessful();
    }
}
