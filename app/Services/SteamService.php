<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SteamService
{
    public static function search(string $query): array
    {
        $term = urlencode(trim($query));

        $url = "https://store.steampowered.com/api/storesearch/?term={$term}&l=english";

        $response = Http::get($url);

        if (!$response->successful()) return [];

        $data = $response->json();

        if (!is_array($data) || !isset($data['items'])) return [];

        $result = [];

        foreach ($data['items'] as $item) {
            $result[] = [
                'steam_app_id' => $item['id'] ?? null,
                'title' => $item['name'] ?? '',
                'image_url' => $item['tiny_image'] ?? ''
            ];
        }

        return $result;
    }
}
