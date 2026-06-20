<x-layout>
    <h1>Tracked Games</h1>

    <ul>
        @if ($trackedGames->isEmpty())
        <li>You are not tracking any games</li>
        @endif
        @foreach ($trackedGames as $trackedGame)
        <li>
            <h2>{{ $trackedGame->game->title }}</h2>
            <p>Current price: {{ $trackedGame->game->current_price }} {{ $trackedGame->game->currency }}</p>
            <p>Target price: {{ $trackedGame->target_price? $trackedGame->target_price . ' USD' : 'Not set' }}</p>
            <a href="/games/{{ $trackedGame->game->steam_app_id }}"><button>View game</button></a>
            <a href="/tracked/{{ $trackedGame->id }}/edit"><button>Edit</button></a>
            <form method="POST" action="/tracked/{{ $trackedGame->id }}">
                @csrf
                @method('DELETE')
                <button type="submit">Untrack</button>
            </form>
        </li>
        @endforeach
    </ul>
</x-layout>
