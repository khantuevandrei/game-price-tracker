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

        <label for="notify_email">Notify by email:</label>
        <input type="checkbox"
            name="notify_email"
            id="notify_email"
            value="1"
            {{ $trackedGame->notify_email? 'checked' : '' }}>

        <label for="notify_telegram">Notify telegram:</label>
        <input type="checkbox"
            name="notify_telegram"
            id="notify_telegram"
            value="1"
            {{ $trackedGame->notify_telegram? 'checked' : '' }}>

        <button type="submit">Save</button>
    </form>
</x-layout>
