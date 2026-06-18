    <h1>Good news!</h1>
    <p>{{ $trackedGame->game->title }} is now {{ $newPrice }} {{ $trackedGame->game->currency}}.</p>
    <p>Your target was {{ $trackedGame->target_price }}.</p>
    <p><a href="{{ url('/games/' . $trackedGame->game->steam_app_id) }}">View game</a></p>
