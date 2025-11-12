<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Addy - Your intelligent business COO. Manage finances, sales, team, and inventory all in one place with AI-powered insights.">
    <title inertia>{{ config('app.name', 'Addy') }}</title>
    <link rel="icon" type="image/png" href="/assets/logos/icon.png">
    <link rel="shortcut icon" type="image/png" href="/assets/logos/icon.png">
    <link rel="apple-touch-icon" href="/assets/logos/icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @routes
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>

