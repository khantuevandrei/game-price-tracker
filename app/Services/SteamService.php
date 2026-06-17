<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SteamService
{
    /*
        Парсим HTML страницу поиска Steam. Извлекаем id и
        title игр. Возвращаем массив.
    */
    public static function search(string $query): array
    {
        $term = urlencode(trim($query));

        $url = "https://store.steampowered.com/search/?term={$term}";

        $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($url);

        if (!$response->ok()) return [];

        $html = $response->body();

        preg_match_all('/data-ds-appid="(\d+)"/', $html, $idMatches);

        preg_match_all('/<span class="title">(.*?)<\/span>/', $html, $titleMatches);

        $ids = $idMatches[1] ?? [];
        $titles = $titleMatches[1] ?? [];

        $result = [];

        foreach ($ids as $index => $id) {
            $result[] = [
                'id' => (int) $id ?? null,
                'title' => html_entity_decode($titles[$index])
            ];
        }

        return $result;
    }

    /*
        Ищем по id через API Steam. Из JSON извлекаем
        детали игры. Возвращаем массив.
    */
    public static function getDetails(int $appId): array
    {
        $url = "https://store.steampowered.com/api/appdetails?appids={$appId}&cc=US";

        $response = Http::get($url);

        if (!$response->ok()) return [];

        $data = $response->json();

        if ($data[$appId]['success'] !== true) return [];

        $game = $data[$appId]['data'];

        $result = [
            'id' => $game['steam_appid'],
            'title' => $game['name'],
            'description' => $game['short_description'],
            'genres' => array_column($game['genres'] ?? [], 'description'),
            'price' => $game['price_overview']['final'] ?? null,
            'currency' => $game['price_overview']['currency'] ?? null,
            'image_url' => $game['header_image']
        ];

        return $result;
    }
}
