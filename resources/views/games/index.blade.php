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
            <a href="/games/{{ $game['id'] }}"><button>View</button></a>
        </div>
        @endforeach
        @endif
    </div>
</x-layout>
