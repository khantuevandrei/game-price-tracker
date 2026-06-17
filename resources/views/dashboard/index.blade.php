<x-layout>
    <h1>My Tracked Games</h1>

    <ul>
        @if ($trackedGames->isEmpty())
        <li>You are not tracking any games</li>
        @endif
        @foreach ($trackedGames as $trackedGame)
        <li>
            <h2>{{ $trackedGame->game->title }}</h2>
            <p>Current price: {{ $trackedGame->game->current_price / 100 }} {{ $trackedGame->game->currency }}</p>
            <p>Target price: {{ $trackedGame->target_price?? 'Not set' }}</p>
            <p>Notify by email: {{ $trackedGame->notify_email? 'On' : 'Off' }}</p>
            <p>Notify by telegram: {{ $trackedGame->notify_telegram? 'On' : 'Off' }}</p>
            <a href="/games/{{ $trackedGame->game->steam_app_id }}">View game</a>
            <form method="POST" action="/tracked/{{ $trackedGame->id }}">
                @csrf
                @method('DELETE')
                <button type="submit">Stop tracking</button>
            </form>
        </li>
        @endforeach
    </ul>
</x-layout>
