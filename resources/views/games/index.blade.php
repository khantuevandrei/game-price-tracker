<x-layout>
    <form action="/" method="GET">
        <label for="search">Find Game:</label>
        <input type="text" name="q" id="search">
        <button type="submit">Search</button>
    </form>
    <div class="cards">
        @if (empty($results) && request('q'))
        <p>Nothing found</p>
        @else
        @foreach ($results as $game)
        <div class="card">
            <h3>{{ $game['title'] }}</h3>
            <img src="{{ $game['image_url'] }}" alt="">
            <form action="/games/{{ $game['steam_app_id'] }}/track" method="POST">
                @csrf
                <button>Track</button>
            </form>
        </div>
        @endforeach
        @endif
    </div>
</x-layout>
