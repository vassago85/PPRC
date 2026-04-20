{{--
    Pretoria Precision Rifle Club — homepage.

    Source of truth: https://pretoriaprc.co.za
    All visible club facts on this page are lifted from the live site. Sections
    that have no source content (matches, results) render a factual empty
    state. Gallery is intentionally omitted because the live site has no
    gallery page.

    Structure (fixed order, per design brief):
        1. Hero
        2. Upcoming matches
        3. Membership
        4. Recent results
        5. Final CTA
--}}
<x-site.layout
    title="Home"
    description="Pretoria Precision Rifle Club — a precision rifle club based in Pretoria, Gauteng. Started in 2023 by precision rifle shooters, for precision rifle shooters."
>

    {{-- =====================================================================
         1. HERO
         Real content from pretoriaprc.co.za:
           • "Precision Rifle Club based in Pretoria, Gauteng"
           • "started in 2023 by Precision Rifle Shooters, For Precision Rifle Shooters"
    ===================================================================== --}}
    <section class="relative isolate overflow-hidden bg-slate-950">
        {{-- subtle radial accent, no imagery --}}
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute left-1/2 top-0 -translate-x-1/2 h-[600px] w-[900px] max-w-full rounded-full bg-white/5 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(255,255,255,0.06),transparent_60%)]"></div>
        </div>

        <x-site.container>
            <div class="py-24 sm:py-32 lg:py-40">
                <x-site.eyebrow>Pretoria · Gauteng</x-site.eyebrow>
                <h1 class="mt-5 text-4xl sm:text-6xl lg:text-7xl font-semibold tracking-tight leading-[1.05] max-w-4xl">
                    Precision rifle shooting in Pretoria.
                </h1>
                <p class="mt-6 max-w-2xl text-lg text-slate-300">
                    Started in 2023 by precision rifle shooters, for precision rifle shooters &mdash;
                    a club for anyone who wants to grow, belong and compete in PRS.
                </p>
                <div class="mt-10 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <x-site.button :href="url('/register')" size="lg">Join PPRC</x-site.button>
                    <x-site.button :href="url('/matches')" size="lg" variant="secondary">View Matches</x-site.button>
                </div>
            </div>
        </x-site.container>
    </section>

    {{-- =====================================================================
         2. UPCOMING MATCHES
         Source state: "No events found" on pretoriaprc.co.za/events
         → show a factual empty state, no invented dates or names.
    ===================================================================== --}}
    <x-site.section tone="muted" padding="default" id="matches">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-10">
            <div>
                <x-site.eyebrow>Upcoming</x-site.eyebrow>
                <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight">Next matches</h2>
            </div>
            <x-site.button :href="url('/matches')" variant="secondary" size="sm">View all matches</x-site.button>
        </div>

        @if ($upcomingMatches->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach ($upcomingMatches as $match)
                    <x-site.card hoverable :href="$match->url ?? url('/matches')">
                        <p class="text-sm text-slate-400">{{ $match->starts_at?->format('D, d M Y') }}</p>
                        <h3 class="mt-2 text-xl font-semibold tracking-tight text-white">{{ $match->title }}</h3>
                        @if ($match->location)
                            <p class="mt-2 text-sm text-slate-400">{{ $match->location }}</p>
                        @endif
                    </x-site.card>
                @endforeach
            </div>
        @else
            <x-site.card padding="lg" class="text-center">
                <p class="text-slate-300">No upcoming matches are listed yet.</p>
                <p class="mt-2 text-sm text-slate-500">Matches will appear here as soon as they are scheduled.</p>
            </x-site.card>
        @endif
    </x-site.section>

    {{-- =====================================================================
         3. MEMBERSHIP
         Source state: pretoriaprc.co.za/membership shows only a portal widget.
         No public tier copy exists → no bulleted tier list. Just the factual
         vision and a clear CTA.

         Vision (verbatim from /about, simplified lightly for flow):
           "create a family/club environment ... uniting together and building
           a sustainable environment where every shooter can grow, belong and
           compete in this wonderful sport of PRS."
    ===================================================================== --}}
    <x-site.section tone="base" padding="lg" id="membership">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16 items-start">
            <div class="lg:col-span-5">
                <x-site.eyebrow>Membership</x-site.eyebrow>
                <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight">
                    A home for precision rifle shooters.
                </h2>
            </div>
            <div class="lg:col-span-7">
                <p class="text-lg text-slate-300 leading-relaxed">
                    PPRC exists to unite precision rifle shooters under one club &mdash;
                    a sustainable environment where every shooter can grow, belong and
                    compete in the sport of PRS.
                </p>
                <p class="mt-5 text-slate-400">
                    Membership is managed through the member portal. Register, pick a
                    membership option, and a committee member will approve your
                    application.
                </p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <x-site.button :href="url('/register')">Become a member</x-site.button>
                    <x-site.button :href="url('/membership')" variant="secondary">Membership details</x-site.button>
                </div>
            </div>
        </div>
    </x-site.section>

    {{-- =====================================================================
         4. RECENT RESULTS
         Source state: pretoriaprc.co.za/results → 404 (page does not exist).
         → factual empty state, no invented results.
    ===================================================================== --}}
    <x-site.section tone="muted" padding="default" id="results">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-10">
            <div>
                <x-site.eyebrow>Results</x-site.eyebrow>
                <h2 class="mt-3 text-3xl sm:text-4xl font-semibold tracking-tight">Recent results</h2>
            </div>
            <x-site.button :href="url('/results')" variant="secondary" size="sm">View results</x-site.button>
        </div>

        @if ($recentResults->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach ($recentResults as $result)
                    <x-site.card hoverable :href="$result->url ?? url('/results')">
                        <p class="text-sm text-slate-400">{{ $result->event_date?->format('d M Y') }}</p>
                        <h3 class="mt-2 text-xl font-semibold tracking-tight text-white">{{ $result->event_title }}</h3>
                        @if ($result->winner)
                            <p class="mt-2 text-sm text-slate-400">Winner &mdash; {{ $result->winner }}</p>
                        @endif
                    </x-site.card>
                @endforeach
            </div>
        @else
            <x-site.card padding="lg" class="text-center">
                <p class="text-slate-300">No results have been posted yet.</p>
                <p class="mt-2 text-sm text-slate-500">Placings and scorecards appear here after each match.</p>
            </x-site.card>
        @endif
    </x-site.section>

    {{-- =====================================================================
         5. FINAL CTA
    ===================================================================== --}}
    <x-site.section tone="accent" padding="lg" id="join">
        <div class="text-center max-w-3xl mx-auto">
            <h2 class="text-4xl sm:text-5xl font-semibold tracking-tight text-slate-950">
                Join Pretoria Precision Rifle Club
            </h2>
            <p class="mt-5 text-slate-600 text-lg">
                Register an account, pick a membership option, and get involved in the next match.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                <x-site.button :href="url('/register')" variant="on-light-primary" size="lg">Join Now</x-site.button>
                <x-site.button :href="url('/contact')" variant="on-light-secondary" size="lg">Contact the club</x-site.button>
            </div>
        </div>
    </x-site.section>

    {{-- =====================================================================
         Optional: latest announcements from the admin (only shown if any
         exist). Kept below the mandatory structure so the landing page stays
         faithful to the brief on a fresh install.
    ===================================================================== --}}
    @if ($announcements->isNotEmpty())
        <x-site.section tone="base" padding="default">
            <div class="flex items-end justify-between mb-8">
                <div>
                    <x-site.eyebrow>News</x-site.eyebrow>
                    <h2 class="mt-3 text-2xl sm:text-3xl font-semibold tracking-tight">Latest from the club</h2>
                </div>
                <a href="{{ url('/news') }}" class="text-sm text-slate-300 hover:text-white">All news →</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach ($announcements as $a)
                    <x-site.card hoverable :href="url('/news/'.$a->slug)">
                        <p class="text-xs text-slate-500">{{ $a->published_at?->format('d M Y') }}</p>
                        <h3 class="mt-2 text-lg font-semibold tracking-tight text-white">{{ $a->title }}</h3>
                        @if ($a->excerpt)
                            <p class="mt-3 text-sm text-slate-400 line-clamp-3">{{ $a->excerpt }}</p>
                        @endif
                    </x-site.card>
                @endforeach
            </div>
        </x-site.section>
    @endif
</x-site.layout>
