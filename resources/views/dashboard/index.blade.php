<x-layout>
    <h1 class="text-2xl font-bold mb-4">My Tracked Games</h1>

    @if ($trackedGames->isEmpty())
    <p class="text-gray-500">You are not tracking any games.</p>
    @else
    <div class="space-y-4">
        @foreach ($trackedGames as $trackedGame)
        <div class="bg-white rounded shadow p-4 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-lg">{{ $trackedGame->game->title }}</h3>
                <p class="text-sm text-gray-500">
                    Current: ${{ number_format($trackedGame->game->current_price, 2) }}
                    | Target: {{ $trackedGame->target_price ? '$'.number_format($trackedGame->target_price, 2) : 'Not set' }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="/games/{{ $trackedGame->game->steam_app_id }}" class="text-blue-500 hover:underline">View</a>
                <a href="/tracked/{{ $trackedGame->id }}/edit" class="text-blue-500 hover:underline">Edit</a>
                <form method="POST" action="/tracked/{{ $trackedGame->id }}">
                    @csrf @method('DELETE')
                    <button class="text-red-500 hover:underline">Untrack</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</x-layout>
