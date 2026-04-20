@php
    $navPages = App\Models\Page::published()->inNav()->get(['slug','title']);
@endphp
<header x-data="{ open: false }" class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2 font-semibold tracking-tight">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-900 text-white text-xs font-bold">PR</span>
                <span>Pretoria Precision Rifle Club</span>
            </a>

            <nav class="hidden md:flex items-center gap-7 text-sm text-slate-600">
                @foreach ($navPages as $p)
                    <a href="{{ url('/'.$p->slug) }}" class="hover:text-slate-900">{{ $p->title }}</a>
                @endforeach
                <a href="{{ url('/events') }}" class="hover:text-slate-900">Events</a>
                <a href="{{ url('/results') }}" class="hover:text-slate-900">Results</a>
                <a href="{{ url('/gallery') }}" class="hover:text-slate-900">Gallery</a>
                <a href="{{ url('/exco') }}" class="hover:text-slate-900">Committee</a>
                <a href="{{ url('/news') }}" class="hover:text-slate-900">News</a>
            </nav>

            <div class="hidden md:flex items-center gap-3">
                @auth
                    <a href="{{ url('/portal/membership') }}" class="text-sm text-slate-600 hover:text-slate-900">Portal</a>
                    <form method="POST" action="{{ url('/logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-slate-600 hover:text-slate-900">Sign out</button>
                    </form>
                @else
                    <a href="{{ url('/login') }}" class="text-sm text-slate-600 hover:text-slate-900">Sign in</a>
                    <a href="{{ url('/register') }}" class="inline-flex items-center rounded-md bg-slate-900 text-white px-3 py-1.5 text-sm hover:bg-slate-800">Join</a>
                @endauth
            </div>

            <button @click="open = !open" class="md:hidden inline-flex items-center justify-center rounded-md p-2 text-slate-700 hover:bg-slate-100">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5m-16.5 5.25h16.5m-16.5 5.25h16.5" />
                    <path x-show="open" stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" style="display:none" />
                </svg>
            </button>
        </div>

        <div x-show="open" x-cloak class="md:hidden pb-4 space-y-1 text-sm">
            @foreach ($navPages as $p)
                <a href="{{ url('/'.$p->slug) }}" class="block rounded-md px-3 py-2 hover:bg-slate-50">{{ $p->title }}</a>
            @endforeach
            <a href="{{ url('/events') }}" class="block rounded-md px-3 py-2 hover:bg-slate-50">Events</a>
            <a href="{{ url('/results') }}" class="block rounded-md px-3 py-2 hover:bg-slate-50">Results</a>
            <a href="{{ url('/gallery') }}" class="block rounded-md px-3 py-2 hover:bg-slate-50">Gallery</a>
            <a href="{{ url('/exco') }}" class="block rounded-md px-3 py-2 hover:bg-slate-50">Committee</a>
            <a href="{{ url('/news') }}" class="block rounded-md px-3 py-2 hover:bg-slate-50">News</a>
            <a href="{{ url('/faqs') }}" class="block rounded-md px-3 py-2 hover:bg-slate-50">FAQs</a>
            @auth
                <a href="{{ url('/portal/membership') }}" class="block rounded-md px-3 py-2 hover:bg-slate-50">My portal</a>
            @else
                <a href="{{ url('/login') }}" class="block rounded-md px-3 py-2 hover:bg-slate-50">Sign in</a>
                <a href="{{ url('/register') }}" class="block rounded-md px-3 py-2 bg-slate-900 text-white">Join</a>
            @endauth
        </div>
    </div>
</header>
