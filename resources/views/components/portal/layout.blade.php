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
                    @if (auth()->user()->canSeeAdminLink())
                        <a href="{{ url('/admin') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-amber-400/30 bg-amber-500/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-amber-200 transition hover:bg-amber-500/20">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827a1.125 1.125 0 00-.42 1.173 6.5 6.5 0 010 1.928 1.125 1.125 0 00.42 1.173l1.004.827a1.125 1.125 0 01.26 1.43l-1.297 2.248a1.125 1.125 0 01-1.37.49l-1.217-.456a1.125 1.125 0 00-1.075.124 6.484 6.484 0 01-.22.127 1.125 1.125 0 00-.645.87l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281a1.125 1.125 0 00-.645-.87 6.5 6.5 0 01-.22-.127 1.125 1.125 0 00-1.075-.124l-1.217.456a1.125 1.125 0 01-1.37-.49l-1.296-2.247a1.125 1.125 0 01.26-1.431l1.003-.827a1.125 1.125 0 00.42-1.173 6.5 6.5 0 010-1.928 1.125 1.125 0 00-.42-1.173l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.248a1.125 1.125 0 011.37-.49l1.217.456c.355.133.75.072 1.075-.124.072-.044.146-.087.22-.127a1.125 1.125 0 00.645-.87l.213-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Admin
                        </a>
                    @endif
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
