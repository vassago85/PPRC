@props(['title' => null, 'description' => null])
<!doctype html>
<html lang="en" class="h-full bg-white antialiased scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · PPRC' : 'Pretoria Precision Rifle Club' }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col font-sans text-slate-900 bg-white">
    <x-site.header />
    <main class="flex-1">
        {{ $slot }}
    </main>
    <x-site.footer />
</body>
</html>
