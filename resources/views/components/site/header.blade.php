@php
    $nav = [
        ['label' => 'Home',       'href' => url('/')],
        ['label' => 'About',      'href' => url('/about')],
        ['label' => 'Membership', 'href' => url('/membership')],
        ['label' => 'FAQs',       'href' => url('/faqs')],
        ['label' => 'Matches',    'href' => url('/matches')],
        ['label' => 'Results',    'href' => url('/results')],
        ['label' => 'Shop',       'href' => url('/shop')],
        ['label' => 'Contact',    'href' => url('/contact')],
    ];

    $current = request()->path() === '/' ? '/' : '/'.trim(request()->path(), '/');
    $isActive = function (string $href) use ($current) {
        $path = parse_url($href, PHP_URL_PATH) ?: '/';
        return $path === $current;
    };
@endphp
<header x-data="{ open: false }" class="sticky top-0 z-40 bg-slate-950/80 backdrop-blur supports-[backdrop-filter]:bg-slate-950/70 border-b border-white/10">
    <x-site.container>
        <div class="flex h-16 items-center justify-between">
            {{-- Logo / wordmark --}}
            <a href="{{ url('/') }}" class="flex items-center gap-3 text-white">
                <img
                    src="{{ asset('pprclogo.png') }}"
                    alt="Pretoria Precision Rifle Club"
                    class="h-10 w-auto"
                    width="40"
                    height="40"
                />
                <span class="hidden md:inline font-semibold tracking-tight">Pretoria Precision Rifle Club</span>
                <span class="md:hidden font-semibold tracking-tight text-sm">PPRC</span>
            </a>

            {{-- Desktop navigation --}}
            <nav class="hidden lg:flex items-center gap-8 text-sm text-slate-300">
                @foreach ($nav as $item)
                    <a href="{{ $item['href'] }}"
                       @class([
                           'transition hover:text-white',
                           'text-white' => $isActive($item['href']),
                       ])>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- Desktop CTA --}}
            <div class="hidden lg:flex items-center gap-3">
                @auth
                    @if (auth()->user()->canSeeAdminLink())
                        <a href="{{ url('/admin') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-amber-400/30 bg-amber-500/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-amber-200 transition hover:bg-amber-500/20">
                            Admin
                        </a>
                    @endif
                    <x-site.button :href="url('/portal')" size="sm">Portal</x-site.button>
                @else
                    <a href="{{ url('/login') }}" class="text-sm text-slate-300 hover:text-white">Sign in</a>
                    <x-site.button :href="url('/register')" size="sm">Join PPRC</x-site.button>
                @endauth
            </div>

            {{-- Mobile hamburger --}}
            <button
                @click="open = !open"
                class="lg:hidden inline-flex items-center justify-center rounded-md p-2 text-slate-300 hover:text-white hover:bg-white/5"
                aria-label="Toggle navigation"
            >
                <svg x-show="!open" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5m-16.5 5.25h16.5m-16.5 5.25h16.5" />
                </svg>
                <svg x-show="open" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Mobile menu --}}
        <div x-show="open" x-cloak x-transition.opacity class="lg:hidden pb-6 pt-2 space-y-1 text-base border-t border-white/10">
            @foreach ($nav as $item)
                <a href="{{ $item['href'] }}"
                   @class([
                       'block rounded-md px-3 py-3 text-slate-300 hover:bg-white/5 hover:text-white',
                       'text-white bg-white/5' => $isActive($item['href']),
                   ])>
                    {{ $item['label'] }}
                </a>
            @endforeach

            <div class="pt-4 space-y-2">
                @auth
                    @if (auth()->user()->canSeeAdminLink())
                        <a href="{{ url('/admin') }}" class="block rounded-md border border-amber-400/30 bg-amber-500/10 px-3 py-3 text-center text-sm font-semibold uppercase tracking-wider text-amber-200 hover:bg-amber-500/20">
                            Admin
                        </a>
                    @endif
                    <x-site.button :href="url('/portal')" fullWidth>Portal</x-site.button>
                @else
                    <a href="{{ url('/login') }}" class="block rounded-md px-3 py-3 text-slate-300 hover:bg-white/5 hover:text-white">Sign in</a>
                    <x-site.button :href="url('/register')" fullWidth>Join PPRC</x-site.button>
                @endauth
            </div>
        </div>
    </x-site.container>
</header>
