<x-layout>
    <div class="bg-white rounded shadow p-6">
        <img src="{{ $game['image_url'] }}" alt="{{ $game['title'] }}" class="w-full rounded mb-4">
        <h1 class="text-2xl font-bold mb-2">{{ $game['title'] }}</h1>

        <p class="text-lg text-green-600 mb-2">
            @if ($game['price'])
            ${{ number_format($game['price'] / 100, 2) }} {{ $game['currency'] }}
            @else
            Free
            @endif
        </p>

        <p class="text-gray-600 mb-2">Genres: {{ implode(', ', $game['genres']) }}</p>
        <div class="text-gray-700 mb-4">{!! $game['description'] !!}</div>

        <div class="flex gap-2 mb-4">
            @if ($trackedGame)
            <form action="/tracked/{{ $trackedGame->id }}" method="POST">
                @csrf
                @method('DELETE')
                <button class="bg-red-500 text-white px-4 py-2 rounded">Untrack</button>
            </form>
            @else
            <form action="/games/{{ $game['steam_app_id'] }}/track" method="POST">
                @csrf
                <button class="bg-blue-500 text-white px-4 py-2 rounded">Track</button>
            </form>
            @endif
        </div>

        <a href="/" class="text-blue-500 hover:underline">← Back to search</a>
    </div>

    @if ($priceHistory->isNotEmpty())
    <div class="bg-white rounded shadow p-6 mt-6">
        <h2 class="text-xl font-bold mb-4">Price History</h2>
        <canvas id="priceChart" width="400" height="200"></canvas>
    </div>

    @php
    $chartLabels = $priceHistory->pluck('recorded_at')->map->format('Y-m-d');
    $chartData = $priceHistory->pluck('price')->map(fn($p) => (float) $p);
    $chartLabel = __('games.price_chart_label', ['currency' => $game['currency']]);
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function() {
            const canvas = document.getElementById('priceChart');
            if (!canvas) return;

            new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: @json($chartLabel),
                        data: @json($chartData),
                        borderColor: 'rgb(59, 130, 246)',
                        tension: 0.2,
                        fill: false,
                    }],
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    },
                },
            });
        })();
    </script>
    @else
    <div class="bg-white rounded shadow p-6 mt-6 text-gray-500">
        No price history yet
    </div>
    @endif
</x-layout>
