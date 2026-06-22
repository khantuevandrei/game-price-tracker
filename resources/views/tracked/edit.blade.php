<x-layout>
    <h1 class="text-2xl font-bold mb-4">Edit: {{ $trackedGame->game->title }}</h1>

    <div class="bg-white rounded shadow p-6 max-w-md">
        <form method="POST" action="/tracked/{{ $trackedGame->id }}">
            @csrf
            @method('PATCH')

            <div class="mb-4">
                <label for="target_price" class="block text-gray-700 mb-1">Target Price</label>
                <input type="number" name="target_price" id="target_price" step="0.01"
                    value="{{ $trackedGame->target_price }}"
                    class="border rounded px-3 py-2 w-full">
            </div>

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Save</button>
            <a href="/dashboard" class="text-gray-500 ml-2">Cancel</a>
        </form>
    </div>
</x-layout>
