{{--
    Pretoria Precision Rifle Club — homepage.

    Source of truth for factual content: https://pretoriaprc.co.za
    Approved hero additions: Est. 2023 · Pretoria · founders named ·
    disciplines (PRS Centerfire, PR22). No invented facts.

    Sections (strict order):
      1. Hero
      2. Upcoming matches
      3. Membership
      4. Recent results
      5. Final CTA
      (Gallery intentionally omitted until real gallery content exists.)
--}}
<x-site.layout
    title="Home"
    description="Pretoria Precision Rifle Club — a precision rifle club in Pretoria, Gauteng, established in 2023. Hosting PRS (Centerfire) and PR22 matches."
>
    {{-- =====================================================================
         1. HERO
         Two-column on desktop: copy + CTAs on the left, logo treatment right.
         Mobile: logo first, then pill, headline, copy, CTAs.
    ===================================================================== --}}
    <section class="relative isolate overflow-hidden bg-slate-950">
        {{-- Subtle premium background. No tactical imagery, no busy textures. --}}
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute -top-40 left-1/2 h-[700px] w-[1200px] max-w-full -translate-x-1/2 rounded-full bg-brand-600/15 blur-[140px]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(29,138,192,0.14),transparent_60%)]"></div>
        </div>

        <x-site.container>
            <div class="grid items-center gap-10 py-12 sm:gap-12 sm:py-20 lg:grid-cols-12 lg:gap-16 lg:py-28">
                {{-- COPY + CTAs. On mobile this comes second (logo treatment
                     first); on desktop it sits on the left (7-col). --}}
                <div class="order-2 lg:order-1 lg:col-span-7">
                    {{-- Pill: centered on mobile/tablet, left-aligned on desktop
                         to sit nicely next to the right-column logo treatment. --}}
                    <div class="flex justify-center lg:justify-start">
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.03] px-3.5 py-1.5 text-slate-200 backdrop-blur">
                            <span class="relative flex h-1.5 w-1.5">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-400 opacity-60"></span>
                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-brand-400"></span>
                            </span>
                            <span class="text-[11px] font-semibold uppercase tracking-[0.2em]">Est. 2023 &middot; Pretoria</span>
                        </div>
                    </div>

                    <h1 class="mt-5 text-[2.15rem] font-semibold leading-[1.1] tracking-tight text-white sm:mt-6 sm:text-5xl sm:leading-[1.05] xl:text-6xl">
                        A home for PRS shooters.
                        <span class="mt-2 block text-brand-400">Grow. Belong. Compete.</span>
                    </h1>

                    {{-- Small highlight sub-line: disciplines we host. Sits between
                         the headline and body copy as a quick factual signal. --}}
                    <p class="mt-5 flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.22em] text-brand-300 sm:text-[13px]">
                        <span class="h-px w-8 bg-brand-400/60" aria-hidden="true"></span>
                        <span>PRS (Centerfire) &amp; PR22 matches</span>
                    </p>

                    <div class="mt-6 max-w-xl space-y-4 text-base leading-relaxed text-slate-300 sm:mt-7 sm:text-lg">
                        <p>
                            PPRC is a precision rifle club in Pretoria, Gauteng, established in 2023 by
                            <span class="text-white">Dirk Pio</span>,
                            <span class="text-white">Warren Britnell</span>,
                            <span class="text-white">Natasha Britnell</span>,
                            <span class="text-white">JC Robertson</span>, and
                            <span class="text-white">Leon Goosen</span> &mdash; built by shooters with
                            the goal of growing the sport and helping develop people who want to shoot PRS.
                        </p>
                        <p>
                            We host <span class="text-white">PRS (Centerfire)</span> and
                            <span class="text-white">PR22</span> matches, focused on helping shooters
                            improve, compete, and progress.
                        </p>
                    </div>

                    <div class="mt-8 flex flex-col gap-3 sm:mt-10 sm:flex-row">
                        <a href="{{ url('/register') }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-6 py-3.5 font-semibold tracking-tight text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-400 sm:w-auto">
                            Join PPRC
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>
                        <a href="{{ url('/matches') }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/15 px-6 py-3.5 font-semibold tracking-tight text-white transition hover:border-white/25 hover:bg-white/5 sm:w-auto">
                            View matches
                        </a>
                    </div>

                    @guest
                        <p class="mt-6 text-sm text-slate-400">
                            Already a member?
                            <a href="{{ route('login') }}" class="font-semibold text-brand-300 transition hover:text-brand-200">Sign in &rarr;</a>
                        </p>
                    @endguest
                </div>

                {{-- LOGO TREATMENT — same premium frame on every breakpoint.
                     Mobile: renders first (order-1), centered, narrower max width.
                     Desktop: renders last (lg:order-2) in the 5-col right column. --}}
                <div class="order-1 lg:order-2 lg:col-span-5">
                    <div class="relative mx-auto aspect-square w-full max-w-[20rem] sm:max-w-sm lg:max-w-md">
                        {{-- Soft radial glow behind the logo --}}
                        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-brand-500/30 via-brand-600/10 to-transparent blur-3xl" aria-hidden="true"></div>

                        {{-- Framed logo card --}}
                        <div class="relative flex h-full w-full items-center justify-center rounded-3xl border border-white/10 bg-white/[0.02] p-6 ring-1 ring-white/5 backdrop-blur-sm sm:p-8 lg:p-10">
                            {{-- Thin corner marks for a composed, intentional feel --}}
                            <span class="absolute left-3 top-3 h-3 w-3 border-l border-t border-white/25 sm:left-4 sm:top-4 sm:h-4 sm:w-4" aria-hidden="true"></span>
                            <span class="absolute right-3 top-3 h-3 w-3 border-r border-t border-white/25 sm:right-4 sm:top-4 sm:h-4 sm:w-4" aria-hidden="true"></span>
                            <span class="absolute bottom-3 left-3 h-3 w-3 border-b border-l border-white/25 sm:bottom-4 sm:left-4 sm:h-4 sm:w-4" aria-hidden="true"></span>
                            <span class="absolute bottom-3 right-3 h-3 w-3 border-b border-r border-white/25 sm:bottom-4 sm:right-4 sm:h-4 sm:w-4" aria-hidden="true"></span>

                            <img
                                src="{{ asset('pprclogo.png') }}"
                                alt="Pretoria Precision Rifle Club logo"
                                class="h-auto w-[80%] drop-shadow-[0_20px_50px_rgba(29,138,192,0.45)] sm:w-[78%]"
                            />
                        </div>

                        {{-- At-a-glance strip: Founded · Based in · Matches. Centered
                             to align visually with the framed logo above. --}}
                        <dl class="mx-auto mt-5 grid max-w-[20rem] grid-cols-3 divide-x divide-white/10 rounded-2xl border border-white/10 bg-white/[0.02] text-center sm:mt-6 sm:max-w-sm lg:max-w-md">
                            <div class="px-2 py-3.5 sm:px-3 sm:py-4">
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Founded</dt>
                                <dd class="mt-1 text-sm font-semibold tabular-nums text-white">2023</dd>
                            </div>
                            <div class="px-2 py-3.5 sm:px-3 sm:py-4">
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Based in</dt>
                                <dd class="mt-1 text-sm font-semibold text-white">Pretoria</dd>
                            </div>
                            <div class="px-2 py-3.5 sm:px-3 sm:py-4">
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Matches</dt>
                                <dd class="mt-1 text-sm font-semibold text-white">PRS &middot; PR22</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </x-site.container>
    </section>

    {{-- =====================================================================
         2. UPCOMING MATCHES  (sits directly under the hero, no filler)
    ===================================================================== --}}
    <section class="relative border-t border-white/5 bg-slate-950 py-14 sm:py-20 lg:py-24">
        <x-site.container>
            <div class="mb-8 flex flex-col gap-4 sm:mb-10 md:flex-row md:items-end md:justify-between">
                <div>
                    <x-site.eyebrow>Upcoming</x-site.eyebrow>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Next matches</h2>
                    <p class="mt-2 max-w-xl text-slate-400">PRS (Centerfire) and PR22 matches hosted by PPRC.</p>
                </div>
                <a href="{{ url('/matches') }}"
                   class="inline-flex items-center gap-1 text-sm font-semibold text-brand-300 transition hover:text-brand-200">
                    View all matches
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>

            @if ($upcomingMatches->isNotEmpty())
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($upcomingMatches->take(3) as $match)
                        <x-site.match-card :match="$match" />
                    @endforeach
                </div>
            @else
                {{-- Honest empty state — live site also currently has no listed events. --}}
                <div class="rounded-2xl border border-dashed border-white/10 bg-white/[0.02] px-5 py-10 text-center sm:px-6 sm:py-14">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full border border-brand-400/20 bg-brand-500/10">
                        <svg class="size-6 text-brand-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                    </div>
                    <p class="font-medium text-white">No upcoming matches listed yet.</p>
                    <p class="mt-1 text-sm text-slate-400">Matches appear here as soon as they're scheduled.</p>
                    <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ url('/matches') }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/15 px-5 py-2.5 text-sm font-semibold text-white transition hover:border-white/25 hover:bg-white/5 sm:w-auto">
                            View all matches
                        </a>
                    </div>
                </div>
            @endif
        </x-site.container>
    </section>

    {{-- =====================================================================
         3. MEMBERSHIP
         Factual summary. No invented benefits — only those supported by the
         source site and approved founding context.
    ===================================================================== --}}
    <section class="relative bg-slate-900 py-14 sm:py-20 lg:py-24">
        <x-site.container>
            <div class="grid items-start gap-10 lg:grid-cols-12 lg:gap-16">
                <div class="lg:col-span-6">
                    <x-site.eyebrow>Membership</x-site.eyebrow>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                        A home for precision rifle shooters.
                    </h2>
                    <p class="mt-4 text-base leading-relaxed text-slate-300 sm:mt-5 sm:text-lg">
                        PPRC exists to unite precision rifle shooters under one club &mdash; building a
                        sustainable environment where every shooter can grow, belong, and compete in PRS.
                    </p>
                    <p class="mt-4 text-slate-400">
                        Membership is managed through the member portal. Register, choose a
                        membership option, and a committee member will approve your application.
                    </p>

                    <div class="mt-7 flex flex-col gap-3 sm:mt-8 sm:flex-row">
                        <a href="{{ url('/register') }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-6 py-3.5 font-semibold tracking-tight text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-400 sm:w-auto">
                            Become a member
                        </a>
                        <a href="{{ url('/membership') }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/15 px-6 py-3.5 font-semibold tracking-tight text-white transition hover:border-white/25 hover:bg-white/5 sm:w-auto">
                            Membership details
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-6">
                    <ul class="grid gap-3 sm:grid-cols-2 sm:gap-4">
                        @foreach ([
                            ['title' => 'PRS & PR22 matches', 'body' => 'Entry into PPRC-hosted PRS (Centerfire) and PR22 matches.'],
                            ['title' => 'Member portal', 'body' => 'Renewals, match entries, and orders in one place.'],
                            ['title' => 'Grow the sport', 'body' => 'Support for new shooters developing in PRS.'],
                            ['title' => 'A club community', 'body' => 'A home built around growth, belonging, and competition.'],
                        ] as $item)
                            <li class="rounded-2xl border border-white/10 bg-white/[0.03] p-4 ring-1 ring-white/5 sm:p-5">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-brand-400/30 bg-brand-500/15">
                                        <svg class="size-3.5 text-brand-200" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-white">{{ $item['title'] }}</p>
                                        <p class="mt-1 text-sm text-slate-400">{{ $item['body'] }}</p>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </x-site.container>
    </section>

    {{-- =====================================================================
         4. RECENT RESULTS
    ===================================================================== --}}
    <section class="relative border-t border-white/5 bg-slate-950 py-14 sm:py-20 lg:py-24">
        <x-site.container>
            <div class="mb-8 flex flex-col gap-4 sm:mb-10 md:flex-row md:items-end md:justify-between">
                <div>
                    <x-site.eyebrow>Results</x-site.eyebrow>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Recent results</h2>
                    <p class="mt-2 max-w-xl text-slate-400">Placings and scorecards from PPRC matches.</p>
                </div>
                <a href="{{ url('/results') }}"
                   class="inline-flex items-center gap-1 text-sm font-semibold text-brand-300 transition hover:text-brand-200">
                    View all results
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>

            @if ($recentResults->isNotEmpty())
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach ($recentResults as $result)
                        <x-site.result-card :result="$result" />
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-white/10 bg-white/[0.02] px-5 py-10 text-center sm:px-6 sm:py-14">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full border border-brand-400/20 bg-brand-500/10">
                        <svg class="size-6 text-brand-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-4.5A3.375 3.375 0 0 0 13.125 10.875h-2.25A3.375 3.375 0 0 0 7.5 14.25v4.5m8.25-12 3-3m0 0-3-3m3 3h-15" />
                        </svg>
                    </div>
                    <p class="font-medium text-white">No results posted yet.</p>
                    <p class="mt-1 text-sm text-slate-400">Placings and scorecards will appear here after each match.</p>
                </div>
            @endif
        </x-site.container>
    </section>

    {{-- =====================================================================
         5. FINAL CTA
    ===================================================================== --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-800 via-brand-700 to-brand-900">
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(255,255,255,0.14),transparent_55%)]"></div>
        </div>
        <x-site.container>
            <div class="mx-auto max-w-3xl py-14 text-center sm:py-20 lg:py-24">
                <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl md:text-5xl">
                    Join Pretoria Precision Rifle Club.
                </h2>
                <p class="mt-4 text-base text-brand-50/90 sm:mt-5 sm:text-lg">
                    Register an account, pick a membership option, and get involved in the next match.
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:mt-9 sm:flex-row">
                    <a href="{{ url('/register') }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-7 py-3.5 font-semibold text-brand-800 shadow-lg shadow-black/20 transition hover:bg-brand-50 sm:w-auto">
                        Join now
                    </a>
                    <a href="{{ url('/contact') }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/30 px-7 py-3.5 font-semibold text-white transition hover:bg-white/10 sm:w-auto">
                        Contact the club
                    </a>
                </div>
            </div>
        </x-site.container>
    </section>
</x-site.layout>
