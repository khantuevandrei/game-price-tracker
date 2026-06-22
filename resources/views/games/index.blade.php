<x-layout>
    <h1 class="text-2xl font-bold mb-4">Game Catalog</h1>

    <form action="/" method="GET" class="mb-6">
        <div class="flex gap-2">
            <input type="text" name="q" placeholder="Search games..."
                class="border rounded px-3 py-2 flex-1" value="{{ request('q') }}">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Search</button>
        </div>
    </form>

    @if (request('q') && empty($results))
    <p class="text-gray-500">Nothing found.</p>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($results as $game)
        <div class="bg-white rounded shadow p-4">
            <h3 class="font-bold text-lg">{{ $game['title'] }}</h3>
            <a href="/games/{{ $game['id'] }}" class="text-blue-500 hover:underline">View details</a>
        </div>
        @endforeach
    </div>
</x-layout>
