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
    <form action="/games/{{ $game['steam_app_id'] }}/track" method="POST">
        @csrf
        <button>Track</button>
    </form>
    <a href="/">Back to search</a>
</x-layout>
