<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('brand.product') }}</title>
        <link rel="icon" type="image/png" href="{{ config('brand.favicon') }}">
        <link rel="apple-touch-icon" href="{{ config('brand.favicon') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="min-h-screen bg-slate-50 font-sans antialiased text-slate-900">
        @inertia
    </body>
</html>
