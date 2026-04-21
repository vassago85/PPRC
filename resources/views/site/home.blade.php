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
<div class="home-page">
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
            <div
                class="absolute inset-0 opacity-[0.2] mix-blend-soft-light"
                style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 256 256%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22n%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.9%22 numOctaves=%224%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23n)%22 opacity=%220.45%22/%3E%3C/svg%3E');"
            ></div>
        </div>

        <x-site.container>
            <div class="grid items-center gap-10 py-12 sm:gap-12 sm:py-20 lg:grid-cols-12 lg:gap-16 lg:py-28">
                {{-- COPY + CTAs. On mobile this comes second (logo treatment
                     first); on desktop it sits on the left (7-col). --}}
                <div class="home-hero-reveal order-2 lg:order-1 lg:col-span-7">
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
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-6 py-3.5 font-semibold tracking-tight text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-400 motion-safe:active:scale-[0.98] sm:w-auto">
                            Join PPRC
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>
                        <a href="{{ url('/matches') }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/15 px-6 py-3.5 font-semibold tracking-tight text-white transition hover:border-white/25 hover:bg-white/5 motion-safe:active:scale-[0.98] sm:w-auto">
                            View matches
                        </a>
                    </div>

                    <p class="mt-5 text-center text-sm text-slate-400 sm:mt-6 sm:text-left">
                        New to PRS or PR22?
                        <a href="{{ route('faqs') }}" class="font-semibold text-brand-300 underline decoration-brand-400/30 underline-offset-4 transition hover:text-brand-200 hover:decoration-brand-200">Read the FAQs</a>
                        <span class="text-slate-500">&mdash;</span>
                        <span class="text-slate-500">gear, match day, and getting started.</span>
                    </p>

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
                        <div class="home-card-hover relative flex h-full w-full items-center justify-center rounded-3xl border border-white/10 bg-white/[0.02] p-6 ring-1 ring-white/5 backdrop-blur-sm motion-safe:hover:border-white/20 sm:p-8 lg:p-10">
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
                        <dl class="home-card-hover mx-auto mt-5 grid max-w-[20rem] grid-cols-3 divide-x divide-white/10 rounded-2xl border border-white/10 bg-white/[0.02] text-center motion-safe:hover:border-white/20 sm:mt-6 sm:max-w-sm lg:max-w-md">
                            <div class="px-2 py-3.5 sm:px-3 sm:py-4">
                                <div class="mx-auto mb-1.5 flex h-7 w-7 items-center justify-center rounded-lg border border-brand-400/25 bg-brand-500/10 text-brand-200">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                </div>
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Founded</dt>
                                <dd class="mt-1 text-sm font-semibold tabular-nums text-white">2023</dd>
                            </div>
                            <div class="px-2 py-3.5 sm:px-3 sm:py-4">
                                <div class="mx-auto mb-1.5 flex h-7 w-7 items-center justify-center rounded-lg border border-white/15 bg-white/[0.06] text-slate-200">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                </div>
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Based in</dt>
                                <dd class="mt-1 text-sm font-semibold text-white">Pretoria</dd>
                            </div>
                            <div class="px-2 py-3.5 sm:px-3 sm:py-4">
                                <div class="mx-auto mb-1.5 flex h-7 w-7 items-center justify-center rounded-lg border border-amber-400/25 bg-amber-500/10 text-amber-100">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2M5.64 5.64l1.41 1.41m10.9 10.9 1.41 1.41M3 12h2m14 0h2M5.64 18.36l1.41-1.41m10.9-10.9 1.41-1.41M12 8.25a3.75 3.75 0 1 0 0 7.5 3.75 3.75 0 0 0 0-7.5Z" />
                                    </svg>
                                </div>
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
                    <div class="inline-flex items-end gap-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-brand-400/25 bg-brand-500/10 text-brand-200" aria-hidden="true">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </span>
                        <x-site.eyebrow>Upcoming</x-site.eyebrow>
                    </div>
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
                <div class="home-card-hover rounded-2xl border border-dashed border-white/10 bg-white/[0.02] px-5 py-10 text-center motion-safe:hover:border-white/20 sm:px-6 sm:py-14">
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
                    <div class="inline-flex items-end gap-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/15 bg-white/[0.06] text-brand-200" aria-hidden="true">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                        </span>
                        <x-site.eyebrow>Membership</x-site.eyebrow>
                    </div>
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

                    <div class="mt-7 flex flex-col flex-wrap gap-3 sm:mt-8 sm:flex-row">
                        <a href="{{ url('/register') }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-6 py-3.5 font-semibold tracking-tight text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-400 motion-safe:active:scale-[0.98] sm:w-auto">
                            Become a member
                        </a>
                        <a href="{{ url('/membership') }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/15 px-6 py-3.5 font-semibold tracking-tight text-white transition hover:border-white/25 hover:bg-white/5 motion-safe:active:scale-[0.98] sm:w-auto">
                            Membership details
                        </a>
                        <a href="{{ route('faqs') }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/15 px-6 py-3.5 font-semibold tracking-tight text-white transition hover:border-white/25 hover:bg-white/5 motion-safe:active:scale-[0.98] sm:w-auto">
                            FAQs
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-6">
                    <ul class="grid gap-3 sm:grid-cols-2 sm:gap-4">
                        @foreach ([
                            ['title' => 'PRS & PR22 matches', 'body' => 'Entry into PPRC-hosted PRS (Centerfire) and PR22 matches.', 'icon' => 'target'],
                            ['title' => 'Member portal', 'body' => 'Renewals, match entries, and orders in one place.', 'icon' => 'stack'],
                            ['title' => 'Grow the sport', 'body' => 'Support for new shooters developing in PRS.', 'icon' => 'trend'],
                            ['title' => 'A club community', 'body' => 'A home built around growth, belonging, and competition.', 'icon' => 'users'],
                        ] as $item)
                            <li class="home-card-hover rounded-2xl border border-white/10 bg-white/[0.03] p-4 ring-1 ring-white/5 motion-safe:hover:border-white/20 sm:p-5">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-brand-400/30 bg-brand-500/15 text-brand-200">
                                        @if ($item['icon'] === 'target')
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2M5.64 5.64l1.41 1.41m10.9 10.9 1.41 1.41M3 12h2m14 0h2M5.64 18.36l1.41-1.41M17.66 6.34l1.41-1.41M12 8.25a3.75 3.75 0 1 0 0 7.5 3.75 3.75 0 0 0 0-7.5Z" />
                                            </svg>
                                        @elseif ($item['icon'] === 'stack')
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6h12v.878M6 12.75h12m-12 0v.878m0-6.756v-.878m12 .878v-.878m0 6.756v.878M6 18.75h12M6 18.75v.878m12-.878v.878M6 12.75H3.375a1.125 1.125 0 0 1-1.125-1.125v-1.5c0-.621.504-1.125 1.125-1.125H6m12 0h2.625c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125H18Z" />
                                            </svg>
                                        @elseif ($item['icon'] === 'trend')
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                                            </svg>
                                        @else
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                            </svg>
                                        @endif
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
                    <div class="inline-flex items-end gap-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/15 bg-white/[0.06] text-slate-200" aria-hidden="true">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-9.75c-.621 0-1.125.504-1.125 1.125V18.75m12-12H3.75c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h16.5c.621 0 1.125-.504 1.125-1.125V7.875c0-.621-.504-1.125-1.125-1.125Z" />
                            </svg>
                        </span>
                        <x-site.eyebrow>Results</x-site.eyebrow>
                    </div>
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
                <div class="home-card-hover rounded-2xl border border-dashed border-white/10 bg-white/[0.02] px-5 py-10 text-center motion-safe:hover:border-white/20 sm:px-6 sm:py-14">
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
                       class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-7 py-3.5 font-semibold text-brand-800 shadow-lg shadow-black/20 transition hover:bg-brand-50 motion-safe:active:scale-[0.98] sm:w-auto">
                        Join now
                    </a>
                    <a href="{{ url('/contact') }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/30 px-7 py-3.5 font-semibold text-white transition hover:bg-white/10 motion-safe:active:scale-[0.98] sm:w-auto">
                        Contact the club
                    </a>
                </div>
                <p class="mt-6 text-sm text-brand-50/85">
                    <a href="{{ route('faqs') }}" class="font-semibold text-white underline decoration-white/35 underline-offset-4 transition hover:decoration-white">Frequently asked questions</a>
                    <span class="text-brand-50/70">&mdash; PRS, PR22, equipment, and what to bring.</span>
                </p>
            </div>
        </x-site.container>
    </section>
</div>
</x-site.layout>
