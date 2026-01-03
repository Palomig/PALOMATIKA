<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - PALOMATIKA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-800 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <a href="/" class="block text-center mb-8">
            <span class="text-3xl font-bold text-white">PALOMATIKA</span>
        </a>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            @yield('content')
        </div>

        <!-- Footer -->
        <p class="text-center text-white/60 text-sm mt-6">
            &copy; {{ date('Y') }} PALOMATIKA. Все права защищены.
        </p>
    </div>
</body>
</html>
