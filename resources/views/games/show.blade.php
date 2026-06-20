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

    @if ($priceHistory->isNotEmpty())
    <h2>Price History</h2>
    <canvas id="priceChart" width="400" height="200"></canvas>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('priceChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($priceHistory->pluck('recorded_at')->map(fn($d) => $d->format('Y-m-d'))) !!},
                datasets: [{
                    label: 'Price (USD)',
                    data: {!! json_encode($priceHistory->pluck('price')) !!},
                    borderColor: 'blue',
                    fill: false
                }]
            }
        });
    </script>
@endif
</x-layout>
