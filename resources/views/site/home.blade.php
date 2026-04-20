{{--
    Pretoria Precision Rifle Club — homepage.

    Content rule: facts only come from pretoriaprc.co.za / the club's own DB.
    No invented stats, no stock hype. Matches & results are empty-state until
    Phase 3 ships the Event / Result models.
--}}
<x-site.layout
    title="Home"
    description="Pretoria Precision Rifle Club — a precision rifle club based in Pretoria, Gauteng. Started in 2023 by precision rifle shooters, for precision rifle shooters."
>
    {{-- =====================================================================
         1. HERO — split layout, live-data panel on the right
    ===================================================================== --}}
    <section class="relative isolate overflow-hidden bg-slate-950">
        {{-- background treatment --}}
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute -top-24 left-1/2 -translate-x-1/2 h-[620px] w-[1100px] max-w-full rounded-full bg-brand-600/20 blur-[120px]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(29,138,192,0.18),transparent_55%)]"></div>
            <div
                class="absolute inset-0 opacity-[0.04]"
                style="background-image: url('data:image/svg+xml,%3Csvg width=%2240%22 height=%2240%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cpath d=%22M0 0h40v40H0z%22 fill=%22none%22/%3E%3Cpath d=%22M40 0v40H0%22 stroke=%22%23fff%22 stroke-width=%220.5%22 fill=%22none%22/%3E%3C/svg%3E');"
            ></div>
        </div>

        <x-site.container>
            <div class="py-20 lg:py-28 grid lg:grid-cols-12 gap-12 lg:gap-14 items-center">
                {{-- LEFT: copy --}}
                <div class="lg:col-span-6">
                    <div class="inline-flex items-center gap-2 rounded-full bg-brand-500/10 border border-brand-400/20 px-3.5 py-1.5 text-brand-200">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-60 animate-ping"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-brand-400"></span>
                        </span>
                        <span class="text-[11px] font-semibold tracking-[0.18em] uppercase">Est. 2023 · Pretoria</span>
                    </div>

                    <h1 class="mt-6 text-4xl sm:text-5xl xl:text-6xl font-semibold tracking-tight leading-[1.05]">
                        Precision rifle,<br>
                        <span class="text-white">built for </span><span class="text-brand-400">shooters.</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-base sm:text-lg text-slate-300 leading-relaxed">
                        PPRC is a precision rifle club in Pretoria, Gauteng &mdash; started in 2023
                        by precision rifle shooters, for precision rifle shooters. A home to grow,
                        belong and compete in PRS.
                    </p>

                    <div class="mt-9 flex flex-col sm:flex-row gap-3">
                        <a href="{{ url('/register') }}"
                           class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 text-white px-6 py-3.5 font-semibold tracking-tight hover:bg-brand-400 transition shadow-lg shadow-brand-600/30">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                            </svg>
                            Join PPRC
                        </a>
                        <a href="{{ url('/matches') }}"
                           class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/15 text-white px-6 py-3.5 font-semibold tracking-tight hover:bg-white/5 hover:border-white/25 transition">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            View matches
                        </a>
                    </div>

                    @guest
                        <p class="mt-6 text-sm text-slate-400">
                            Already a member?
                            <a href="{{ route('login') }}" class="text-brand-300 hover:text-brand-200 font-semibold transition">Sign in &rarr;</a>
                        </p>
                    @endguest
                </div>

                {{-- RIGHT: live data panel --}}
                <div class="lg:col-span-6">
                    <div class="relative rounded-2xl bg-white/[0.03] border border-white/10 backdrop-blur-sm shadow-2xl shadow-black/40 overflow-hidden ring-1 ring-white/5">
                        {{-- panel header --}}
                        <div class="flex items-center gap-2.5 px-5 py-3 bg-white/[0.02] border-b border-white/10">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-60 animate-ping"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-brand-400"></span>
                            </span>
                            <span class="text-[11px] font-bold tracking-[0.2em] uppercase text-slate-200">PPRC Live</span>
                            <span class="ml-auto text-[10px] tabular-nums text-slate-500 font-medium">2026 Season</span>
                        </div>

                        <div class="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-white/10">
                            {{-- Next matches --}}
                            <div class="p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Next matches</h3>
                                    <a href="{{ url('/matches') }}" class="text-[11px] text-brand-300 hover:text-brand-200 font-semibold transition">All &rarr;</a>
                                </div>

                                @if ($upcomingMatches->isNotEmpty())
                                    <div class="space-y-2">
                                        @foreach ($upcomingMatches->take(3) as $match)
                                            <a href="{{ $match->url ?? url('/matches') }}"
                                               class="block rounded-lg bg-white/[0.03] border border-white/10 hover:border-white/20 hover:bg-white/[0.05] p-3 transition">
                                                <div class="flex items-start gap-3">
                                                    <div class="flex flex-col items-center justify-center rounded-md bg-brand-500/10 border border-brand-400/20 w-11 h-11 shrink-0">
                                                        <span class="text-[8px] font-bold uppercase tracking-wider text-brand-300 leading-none">{{ $match->starts_at?->format('M') }}</span>
                                                        <span class="text-[13px] font-bold text-brand-100 leading-tight">{{ $match->starts_at?->format('d') }}</span>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-[13px] font-semibold text-white truncate">{{ $match->title }}</p>
                                                        @if ($match->location)
                                                            <p class="mt-0.5 text-[11px] text-slate-400 truncate">{{ $match->location }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center text-center py-8">
                                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-white/5 border border-white/10">
                                            <svg class="size-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                            </svg>
                                        </div>
                                        <p class="text-sm text-slate-300 font-medium">No matches scheduled</p>
                                        <p class="mt-1 text-xs text-slate-500">New matches land here as soon as they're announced.</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Quick facts --}}
                            <div class="p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-400">About the club</h3>
                                </div>
                                <dl class="space-y-3">
                                    <div class="flex items-center justify-between gap-4 rounded-lg px-3 py-2.5 bg-white/[0.02] border border-white/5">
                                        <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Founded</dt>
                                        <dd class="text-sm font-semibold text-white tabular-nums">2023</dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 rounded-lg px-3 py-2.5 bg-white/[0.02] border border-white/5">
                                        <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Based in</dt>
                                        <dd class="text-sm font-semibold text-white">Pretoria, GP</dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 rounded-lg px-3 py-2.5 bg-white/[0.02] border border-white/5">
                                        <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Discipline</dt>
                                        <dd class="text-sm font-semibold text-white">PRS</dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 rounded-lg px-3 py-2.5 bg-white/[0.02] border border-white/5">
                                        <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Membership</dt>
                                        <dd class="text-sm font-semibold text-brand-300">
                                            <a href="{{ url('/register') }}" class="hover:text-brand-200 transition">Open →</a>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-site.container>
    </section>

    {{-- =====================================================================
         2. UPCOMING MATCHES
    ===================================================================== --}}
    <section class="relative bg-slate-900 py-20 sm:py-24 border-t border-white/5">
        <x-site.container>
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-10">
                <div>
                    <x-site.eyebrow>Upcoming</x-site.eyebrow>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight">Next matches</h2>
                    <p class="mt-2 text-slate-400 max-w-xl">Club and open-entry PRS matches hosted at PPRC.</p>
                </div>
                <a href="{{ url('/matches') }}"
                   class="hidden sm:inline-flex items-center gap-1 text-sm text-brand-300 hover:text-brand-200 font-semibold transition">
                    View all matches
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>

            @if ($upcomingMatches->isNotEmpty())
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ($upcomingMatches as $match)
                        <a href="{{ $match->url ?? url('/matches') }}"
                           class="group rounded-2xl bg-white/[0.03] border border-white/10 hover:border-brand-400/40 hover:bg-white/[0.05] transition-all duration-200 p-5 flex flex-col hover:-translate-y-0.5">
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="flex flex-col items-center justify-center rounded-xl bg-brand-500/10 border border-brand-400/20 w-14 h-14 shrink-0">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-brand-300 leading-none">{{ $match->starts_at?->format('M') }}</span>
                                    <span class="text-lg font-bold text-brand-100 leading-tight">{{ $match->starts_at?->format('d') }}</span>
                                </div>
                                @if ($match->is_featured ?? false)
                                    <span class="rounded-md bg-amber-400/10 text-amber-300 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ring-1 ring-inset ring-amber-400/30">Featured</span>
                                @endif
                            </div>
                            <h3 class="font-semibold text-white group-hover:text-brand-200 transition leading-snug line-clamp-2 mb-2">{{ $match->title }}</h3>
                            @if ($match->location)
                                <p class="flex items-center gap-1.5 text-sm text-slate-400 mb-3">
                                    <svg class="size-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 0 1 15 0Z" />
                                    </svg>
                                    <span class="truncate">{{ $match->location }}</span>
                                </p>
                            @endif
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl bg-white/[0.02] border border-dashed border-white/10 px-6 py-14 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-500/10 border border-brand-400/20">
                        <svg class="size-6 text-brand-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                    </div>
                    <p class="text-white font-medium">No upcoming matches are listed yet.</p>
                    <p class="mt-1 text-sm text-slate-400">Matches appear here as soon as they're scheduled.</p>
                </div>
            @endif
        </x-site.container>
    </section>

    {{-- =====================================================================
         3. MEMBERSHIP — split: vision + action card
    ===================================================================== --}}
    <section class="relative bg-slate-950 py-20 sm:py-28">
        <x-site.container>
            <div class="grid lg:grid-cols-12 gap-10 lg:gap-16 items-start">
                <div class="lg:col-span-6">
                    <x-site.eyebrow>Membership</x-site.eyebrow>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight">
                        A home for precision rifle shooters.
                    </h2>
                    <p class="mt-5 text-lg text-slate-300 leading-relaxed">
                        PPRC exists to unite precision rifle shooters under one club &mdash;
                        a sustainable environment where every shooter can grow, belong and
                        compete in the sport of PRS.
                    </p>
                    <p class="mt-4 text-slate-400">
                        Membership is managed through the member portal. Register, pick a
                        membership option, and a committee member will approve your application.
                    </p>
                </div>

                <div class="lg:col-span-6">
                    <div class="relative rounded-2xl bg-gradient-to-br from-brand-600/15 via-white/[0.02] to-transparent border border-brand-400/20 p-8 sm:p-10 ring-1 ring-white/5">
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-500/20 border border-brand-400/30">
                                    <svg class="size-3.5 text-brand-200" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </span>
                                <p class="text-slate-200">Entry into PPRC-hosted PRS matches</p>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-500/20 border border-brand-400/30">
                                    <svg class="size-3.5 text-brand-200" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </span>
                                <p class="text-slate-200">Member portal for renewals, entries and orders</p>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-500/20 border border-brand-400/30">
                                    <svg class="size-3.5 text-brand-200" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </span>
                                <p class="text-slate-200">Juniors under 18 free with a member parent</p>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-500/20 border border-brand-400/30">
                                    <svg class="size-3.5 text-brand-200" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </span>
                                <p class="text-slate-200">A community built around growth, belonging and competition</p>
                            </li>
                        </ul>

                        <div class="mt-8 flex flex-col sm:flex-row gap-3">
                            <a href="{{ url('/register') }}"
                               class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 text-white px-5 py-3 font-semibold hover:bg-brand-400 transition shadow-lg shadow-brand-600/30">
                                Become a member
                            </a>
                            <a href="{{ url('/membership') }}"
                               class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/15 text-white px-5 py-3 font-semibold hover:bg-white/5 transition">
                                Membership details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </x-site.container>
    </section>

    {{-- =====================================================================
         4. RECENT RESULTS
    ===================================================================== --}}
    <section class="relative bg-slate-900 py-20 sm:py-24 border-t border-white/5">
        <x-site.container>
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-10">
                <div>
                    <x-site.eyebrow>Results</x-site.eyebrow>
                    <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight">Recent results</h2>
                    <p class="mt-2 text-slate-400 max-w-xl">Placings and scorecards from PPRC matches.</p>
                </div>
                <a href="{{ url('/results') }}"
                   class="hidden sm:inline-flex items-center gap-1 text-sm text-brand-300 hover:text-brand-200 font-semibold transition">
                    View all results
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>

            @if ($recentResults->isNotEmpty())
                <div class="grid sm:grid-cols-2 gap-5">
                    @foreach ($recentResults as $result)
                        <a href="{{ $result->url ?? url('/results') }}"
                           class="group rounded-2xl bg-white/[0.03] border border-white/10 hover:border-brand-400/40 hover:bg-white/[0.05] transition-all duration-200 p-5 flex items-start gap-4">
                            <div class="flex flex-col items-center justify-center rounded-xl bg-brand-500/10 border border-brand-400/20 w-14 h-14 shrink-0">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-brand-300 leading-none">{{ $result->event_date?->format('M') }}</span>
                                <span class="text-lg font-bold text-brand-100 leading-tight">{{ $result->event_date?->format('d') }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-white group-hover:text-brand-200 transition leading-snug line-clamp-2">{{ $result->event_title }}</h3>
                                @if ($result->winner)
                                    <p class="mt-2 text-sm text-slate-400">Winner &mdash; <span class="text-slate-200 font-medium">{{ $result->winner }}</span></p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl bg-white/[0.02] border border-dashed border-white/10 px-6 py-14 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-500/10 border border-brand-400/20">
                        <svg class="size-6 text-brand-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-4.5A3.375 3.375 0 0 0 13.125 10.875h-2.25A3.375 3.375 0 0 0 7.5 14.25v4.5m8.25-12 3-3m0 0-3-3m3 3h-15" />
                        </svg>
                    </div>
                    <p class="text-white font-medium">No results have been posted yet.</p>
                    <p class="mt-1 text-sm text-slate-400">Placings and scorecards appear here after each match.</p>
                </div>
            @endif
        </x-site.container>
    </section>

    {{-- =====================================================================
         5. ANNOUNCEMENTS (only when there are any)
    ===================================================================== --}}
    @if ($announcements->isNotEmpty())
        <section class="relative bg-slate-950 py-20 sm:py-24">
            <x-site.container>
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-10">
                    <div>
                        <x-site.eyebrow>News</x-site.eyebrow>
                        <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight">Latest from the club</h2>
                    </div>
                    <a href="{{ url('/news') }}" class="hidden sm:inline-flex items-center gap-1 text-sm text-brand-300 hover:text-brand-200 font-semibold transition">
                        All news
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ($announcements as $a)
                        <a href="{{ url('/news/'.$a->slug) }}"
                           class="group rounded-2xl bg-white/[0.03] border border-white/10 hover:border-brand-400/40 hover:bg-white/[0.05] transition-all duration-200 p-5 flex flex-col hover:-translate-y-0.5">
                            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">{{ $a->published_at?->format('d M Y') }}</p>
                            <h3 class="mt-2 text-lg font-semibold text-white group-hover:text-brand-200 transition">{{ $a->title }}</h3>
                            @if ($a->excerpt)
                                <p class="mt-3 text-sm text-slate-400 line-clamp-3">{{ $a->excerpt }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </x-site.container>
        </section>
    @endif

    {{-- =====================================================================
         6. FINAL CTA
    ===================================================================== --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-brand-800">
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div
                class="absolute inset-0 opacity-[0.08]"
                style="background-image: url('data:image/svg+xml,%3Csvg width=%2240%22 height=%2240%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cpath d=%22M0 0h40v40H0z%22 fill=%22none%22/%3E%3Cpath d=%22M40 0v40H0%22 stroke=%22%23fff%22 stroke-width=%220.5%22 fill=%22none%22/%3E%3C/svg%3E');"
            ></div>
        </div>

        <x-site.container>
            <div class="py-20 sm:py-24 text-center max-w-3xl mx-auto">
                <h2 class="text-3xl sm:text-5xl font-semibold tracking-tight text-white">
                    Join Pretoria Precision Rifle Club.
                </h2>
                <p class="mt-5 text-lg text-brand-100/90">
                    Register an account, pick a membership option, and get involved in the next match.
                </p>
                <div class="mt-9 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ url('/register') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-white text-brand-700 px-7 py-3.5 font-semibold hover:bg-brand-50 transition shadow-lg shadow-black/20">
                        Join now
                    </a>
                    <a href="{{ url('/contact') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/30 text-white px-7 py-3.5 font-semibold hover:bg-white/10 transition">
                        Contact the club
                    </a>
                </div>
            </div>
        </x-site.container>
    </section>
</x-site.layout>
