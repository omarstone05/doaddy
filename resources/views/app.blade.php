<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Addy - Your intelligent business COO. Manage finances, sales, team, and inventory all in one place with AI-powered insights.">
    <title inertia>{{ config('app.name', 'Addy') }}</title>
    <link rel="icon" type="image/webp" href="/assets/logos/icon.webp">
    <link rel="shortcut icon" type="image/webp" href="/assets/logos/icon.webp">
    <link rel="apple-touch-icon" href="/assets/logos/icon.webp">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500&family=Gochi+Hand&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- Add Noir Pro: place font files in public/fonts/ and uncomment:
    <style>
    @font-face { font-family: 'Noir Pro'; src: url('/fonts/NoirPro-Regular.woff2') format('woff2'); font-weight: 400; font-style: normal; }
    </style>
    --}}
    @routes
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>

