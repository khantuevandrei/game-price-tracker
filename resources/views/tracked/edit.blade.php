<x-layout>
    <h1>Edit: {{ $trackedGame->game->title }}</h1>
    <form method="POST" action="/tracked/{{ $trackedGame->id }}">
        @csrf
        @method('PATCH')

        <label for="target_price">Target price:</label>
        <input type="number"
            name="target_price"
            id="target_price"
            step="0.01"
            value="{{ $trackedGame->target_price }}">

        <button type="submit">Save</button>
    </form>
</x-layout>
