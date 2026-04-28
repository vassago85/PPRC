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
    <header class="border-b border-slate-200/80 bg-white/90 backdrop-blur-md">
        <div class="mx-auto flex max-w-4xl flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('portal.dashboard') }}" class="text-base font-semibold tracking-tight text-slate-900">PPRC Portal</a>
            <nav class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-600">
                <a href="{{ route('portal.dashboard') }}" class="motion-safe transition hover:text-slate-900 {{ request()->routeIs('portal.dashboard') ? 'text-slate-900 font-medium' : '' }}">Dashboard</a>
                <a href="{{ route('portal.membership') }}" class="motion-safe transition hover:text-slate-900 {{ request()->routeIs('portal.membership') ? 'text-slate-900 font-medium' : '' }}">Membership</a>
                <a href="{{ route('portal.registrations') }}" class="motion-safe transition hover:text-slate-900 {{ request()->routeIs('portal.registrations') ? 'text-slate-900 font-medium' : '' }}">My Registrations</a>
                <a href="{{ route('portal.results') }}" class="motion-safe transition hover:text-slate-900 {{ request()->routeIs('portal.results') ? 'text-slate-900 font-medium' : '' }}">My Results</a>
                <a href="{{ route('shop') }}" class="motion-safe transition hover:text-slate-900">Shop</a>
                @auth
                    <a href="{{ route('portal.profile.edit') }}" class="motion-safe transition hover:text-slate-900 {{ request()->routeIs('portal.profile.edit') ? 'text-slate-900 font-medium' : '' }}">Profile</a>
                    <form method="POST" action="{{ url('/logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="motion-safe text-slate-500 transition hover:text-slate-900">Sign out</button>
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
