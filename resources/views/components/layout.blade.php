<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Price Tracker</title>
</head>

<body>
    <nav>
        <a href="/">Catalog</a>
        @auth
        <a href="/dashboard">Dashboard</a>
        <a href="/profile">Profile</a>
        @else
        <a href="/login">Login</a>
        <a href="/register">Register</a>
        @endauth
    </nav>

    @if (session('success'))
    <div style="color: green;">{{ session('success') }}</div>
    @endif

    @if (session('error'))
    <div style="color: red;">{{ session('error') }}</div>
    @endif

    <hr>

    {{ $slot }}

    <footer>Game Price Tracker © 2026</footer>
</body>

</html>
