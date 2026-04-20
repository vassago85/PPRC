@props(['title' => null, 'description' => null])
<!doctype html>
<html lang="en" class="h-full bg-slate-950 antialiased scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#020617">
    <link rel="icon" type="image/png" href="{{ asset('pprclogo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('pprclogo.png') }}">
    <title>{{ $title ? $title.' · PPRC' : 'Pretoria Precision Rifle Club' }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col font-sans text-white bg-slate-950 selection:bg-white selection:text-slate-950">
    <x-site.header />
    <main class="flex-1">
        {{ $slot }}
    </main>
    <x-site.footer />
</body>
</html>
