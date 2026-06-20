<x-layout>
    <img src="{{ $game['image_url'] }}" alt="{{ $game['title'] }}">
    <h1>{{ $game['title'] }}</h1>
    <p>
        @if ($game['price'])
        {{ $game['price'] / 100 . ' ' . $game['currency'] }}
        @else
        Free
        @endif
    </p>
    <p>
        Genres: {{ implode(', ', $game['genres']) }}
    </p>
    <p>{!! $game['description'] !!}</p>
    @if ($trackedGame)
    <form action="/tracked/{{ $trackedGame->id }}" method="POST">
        @csrf
        @method('DELETE')
        <button>Untrack</button>
    </form>
    @else
    <form action="/games/{{ $game['steam_app_id'] }}/track" method="POST">
        @csrf
        <button>Track</button>
    </form>
    @endif
    <a href="/">Back to search</a>
</x-layout>
