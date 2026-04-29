<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Portal' }} · PPRC</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-slate-950 font-sans text-white antialiased">
    <header class="border-b border-white/10 bg-slate-950/80 backdrop-blur-md">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-5 py-4">
            <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-3">
                <img src="{{ asset('pprclogo.png') }}" alt="PPRC" class="h-8 w-auto" width="32" height="32" />
                <span class="text-sm font-semibold tracking-tight text-white">Member Portal</span>
            </a>
            <nav class="flex items-center gap-6 text-sm">
                <a href="{{ route('portal.dashboard') }}" class="transition {{ request()->routeIs('portal.dashboard') ? 'text-white' : 'text-slate-400 hover:text-white' }}">Dashboard</a>
                <a href="{{ route('portal.membership') }}" class="transition {{ request()->routeIs('portal.membership') ? 'text-white' : 'text-slate-400 hover:text-white' }}">Membership</a>
                <a href="{{ route('portal.documents') }}" class="transition {{ request()->routeIs('portal.documents*') ? 'text-white' : 'text-slate-400 hover:text-white' }}">Documents</a>
                <a href="{{ route('shop') }}" class="text-slate-400 transition hover:text-white">Shop</a>
                @auth
                    <form method="POST" action="{{ url('/logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-slate-500 transition hover:text-white">Sign out</button>
                    </form>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-5 py-10">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
