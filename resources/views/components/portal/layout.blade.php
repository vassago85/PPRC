<!doctype html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Portal' }} · PPRC</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full font-sans text-slate-900">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ url('/portal/membership') }}" class="font-semibold tracking-tight">PPRC Portal</a>
            <nav class="flex items-center gap-4 text-sm text-slate-600">
                <a href="{{ url('/portal/membership') }}" class="hover:text-slate-900">Membership</a>
                @auth
                    <form method="POST" action="{{ url('/logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="hover:text-slate-900">Sign out</button>
                    </form>
                @endauth
            </nav>
        </div>
    </header>
    <main>
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
