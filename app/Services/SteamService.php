<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SteamService
{
    public static function search(string $query): array
    {
        $term = urlencode(trim($query));

        $url = "https://store.steampowered.com/search/?term={$term}";

        $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($url);

        if (!$response->ok()) return [];

        $html = $response->body();

        // Ищем по app_id
        preg_match_all('/data-ds-appid="(\d+)"/', $html, $idMatches);

        // Находим названия
        preg_match_all('/<span class="title">(.*?)<\/span>/', $html, $titleMatches);

        $ids = $idMatches[1] ?? [];
        $titles = $titleMatches[1] ?? [];

        $result = [];

        foreach ($ids as $index => $id) {
            $result[] = [
                'steam_app_id' => (int) $id ?? null,
                'title' => html_entity_decode($titles[$index])
            ];
        }

        return $result;
    }

    public static function getDetails(int $appId): array
    {
        $url = "https://store.steampowered.com/api/appdetails?appids={$appId}";

        $response = Http::get($url);

        if (!$response->ok()) return [];

        $data = $response->json();

        if ($data['success'] !== true) return [];

        $data = $data[$appId];
        $data = $data['data'];

        $result = [
            'steam_app_id' => $data['steam_appid'],
            'title' => $data['name'],
            'description' => $data['short_description'],
            'genres' => $data['genres'],
            'price' => $data['price_overview']['final'],
            'currency' => $data['price_overview']['currency'],
            'image_url' => $data['header_image']
        ];

        return $result;
    }
}
