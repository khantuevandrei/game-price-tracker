<?php

namespace Tests\Unit;

use App\Services\SteamService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SteamServiceTest extends TestCase
{
    public function test_search_returns_games(): void
    {
        Http::fake([
            'store.steampowered.com/search/*' => Http::response(
                '<a data-ds-appid="1245620"><span class="title">ELDEN RING</span></a>'.
                    '<a data-ds-appid="123"><span class="title">Test Game</span></a>',
                200
            ),
        ]);

        $results = SteamService::search('Elden');

        $this->assertCount(2, $results);
        $this->assertEquals(1245620, $results[0]['id']);
        $this->assertEquals('ELDEN RING', $results[0]['title']);
    }

    public function test_search_returns_empty_on_failure(): void
    {
        Http::fake([
            'store.steampowered.com/*' => Http::response('', 500),
        ]);

        $results = SteamService::search('Elden');

        $this->assertEmpty($results);
    }

    public function test_get_details_returns_game_info(): void
    {
        Http::fake([
            'store.steampowered.com/api/appdetails*' => Http::response([
                '1245620' => [
                    'success' => true,
                    'data' => [
                        'steam_appid' => 1245620,
                        'name' => 'ELDEN RING',
                        'short_description' => 'Rise, Tarnished',
                        'genres' => [['description' => 'Action'], ['description' => 'RPG']],
                        'price_overview' => ['final' => 3999, 'currency' => 'USD'],
                        'header_image' => 'https://example.com/image.jpg',
                    ],
                ],
            ], 200),
        ]);

        $details = SteamService::getDetails(1245620);

        $this->assertEquals('ELDEN RING', $details['title']);
        $this->assertEquals(3999, $details['price']);
        $this->assertEquals('USD', $details['currency']);
        $this->assertCount(2, $details['genres']);
    }

    public function test_get_details_returns_empty_on_failure(): void
    {
        Http::fake([
            'store.steampowered.com/api/appdetails*' => Http::response('', 500),
        ]);

        $details = SteamService::getDetails(1245620);

        $this->assertEmpty($details);
    }
}
