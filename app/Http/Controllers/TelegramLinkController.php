<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TelegramLinkController extends Controller
{
    public function generate(Request $request)
    {
        $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

        Cache::put('tg_link:' . $code, $request->user()->id, 600);

        return response()->json([
            'code' => $code,
            'bot_username' => config('services.telegram.username'),
            'expires_in' => 600,
        ]);
    }

    public function unlink(Request $request)
    {
        $request->user()->update(['telegram_id' => null]);

        return response()->json(['ok' => true]);
    }
}
