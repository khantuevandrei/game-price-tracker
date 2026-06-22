<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Price Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <nav class="bg-white shadow mb-6">
        <div class="max-w-4xl px-4 py-3 flex gap-4">
            @auth
            <a href="/" class="text-blue-600 font-semibold">Catalog</a>
            <a href="/dashboard" class="text-blue-600">Dashboard</a>
            <a href="/profile" class="text-blue-600">Profile</a>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button class="text-red-500">Logout</button>
            </form>
            @else
            <a href="/login" class="text-blue-600">Login</a>
            <a href="/register" class="text-blue-600">Register</a>
            @endauth
        </div>
    </nav>

    <main class="max-w-4xl px-4 flex-1">
        @if (session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if (session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ session('error') }}</div>
        @endif

        {{ $slot }}
    </main>

    <footer class="text-center text-gray-500 text-sm py-4 mt-8">
        Game Price Tracker © 2026
    </footer>
</body>

</html>
