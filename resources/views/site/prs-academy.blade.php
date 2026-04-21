@php
    $contactApply = route('contact', ['subject' => 'PPRC PRS Academy — Apply / Enquire']);
    $contactGeneral = route('contact', ['subject' => 'PPRC PRS Academy']);
@endphp

<x-site.layout
    title="PRS Academy"
    description="PPRC PRS Academy — structured 6-week precision rifle training in Pretoria, Gauteng. Practical PRS coaching, weekly range sessions, and beginner-to-match-ready progression for serious South African shooters."
>
<div class="pb-28 lg:pb-0">
    {{-- Hero: bold, performance-led, supports future background image via layered surfaces --}}
    <section class="relative isolate overflow-hidden border-b border-white/10 bg-slate-950">
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_120%_80%_at_50%_-20%,rgba(46,151,212,0.22),transparent_55%)]"></div>
            <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(2,6,23,0.2),rgba(2,6,23,0.92))]"></div>
            <div
                class="absolute inset-0 opacity-[0.35] mix-blend-soft-light"
                style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 256 256%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22n%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.9%22 numOctaves=%224%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23n)%22 opacity=%220.5%22/%3E%3C/svg%3E');"
            ></div>
        </div>

        <x-site.container class="relative py-20 sm:py-28 lg:py-32">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-2 rounded-full border border-amber-400/25 bg-amber-500/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-amber-100">
                    <span class="size-1.5 rounded-full bg-amber-400 shadow-[0_0_12px_rgba(251,191,36,0.6)]" aria-hidden="true"></span>
                    Limited to 6 shooters
                </span>
                <span class="hidden text-xs font-medium uppercase tracking-[0.2em] text-slate-500 sm:inline">Structured · Practical · Match focused</span>
            </div>

            <p class="mt-8 text-xs font-semibold uppercase tracking-[0.28em] text-brand-200 sm:text-sm">
                PPRC PRS Academy
            </p>
            <h1 class="mt-3 max-w-4xl text-3xl font-semibold leading-[1.08] tracking-tight text-white sm:text-4xl lg:text-5xl">
                Everyone thinks they’re a good shooter… until their first PRS match.
            </h1>
            <p class="mt-6 max-w-2xl text-lg leading-relaxed text-slate-200 sm:text-xl">
                Build real PRS skills — with structured coaching, live online sessions, and practical range days every week.
            </p>
            <p class="mt-4 max-w-xl text-sm leading-relaxed text-slate-400 sm:text-base">
                A six-week, instructor-led program for serious beginners who want to enter precision rifle competition with intent — not guesswork.
            </p>

            <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <x-site.button href="{{ $contactApply }}" variant="primary" size="lg" class="shadow-lg shadow-black/40">
                    Apply / Enquire Now
                </x-site.button>
                <x-site.button href="#program" variant="secondary" size="lg">
                    View Program
                </x-site.button>
            </div>
            <p class="mt-6 text-xs text-slate-500 sm:text-sm">
                <span class="text-slate-400 sm:hidden">Structured · Practical · Match focused</span>
            </p>
        </x-site.container>
    </section>

    {{-- Program overview --}}
    <x-site.section tone="muted" padding="lg" id="program">
        <div class="mx-auto max-w-3xl text-center">
            <x-site.eyebrow>Program</x-site.eyebrow>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">PPRC PRS Academy</h2>
            <p class="mt-4 text-slate-400">
                Precision rifle training built around real match pressure — the standard for beginner PRS training and practical precision rifle coaching in Pretoria.
            </p>
        </div>

        <div class="mx-auto mt-14 grid max-w-5xl gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-site.card tone="raised" padding="lg" class="ring-1 ring-white/5">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-brand-400/30 bg-brand-500/10">
                    <svg class="size-5 text-brand-200" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5" />
                    </svg>
                </div>
                <p class="mt-5 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Duration</p>
                <p class="mt-1 text-2xl font-semibold tracking-tight text-white">6 weeks</p>
                <p class="mt-2 text-sm text-slate-400">Structured progression from fundamentals to match execution.</p>
            </x-site.card>

            <x-site.card tone="raised" padding="lg" class="ring-1 ring-white/5">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-white/15 bg-white/[0.06]">
                    <svg class="size-5 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                </div>
                <p class="mt-5 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Format</p>
                <p class="mt-1 text-lg font-semibold leading-snug text-white">Online + range</p>
                <p class="mt-2 text-sm text-slate-400">Instructor-led online sessions each week, plus a same-week practical range day to apply what you learned.</p>
            </x-site.card>

            <x-site.card tone="raised" padding="lg" class="ring-1 ring-amber-400/15 sm:col-span-2 lg:col-span-1">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-amber-400/30 bg-amber-500/10">
                    <svg class="size-5 text-amber-200" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <p class="mt-5 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Intake</p>
                <p class="mt-1 text-2xl font-semibold tracking-tight text-white">Max 6 shooters</p>
                <p class="mt-2 text-sm text-slate-400">Small cohort — coaching stays sharp and personal.</p>
            </x-site.card>

            <x-site.card tone="raised" padding="lg" class="border-brand-400/25 bg-gradient-to-br from-brand-950/40 to-slate-950 ring-1 ring-brand-400/20 sm:col-span-2 lg:col-span-2">
                <div class="grid gap-8 sm:grid-cols-2 sm:items-end">
                    <div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-brand-400/40 bg-brand-500/15">
                            <svg class="size-5 text-brand-100" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75m0 0V21m0-18.75h-18M2.25 16.5h.008v.008H2.25v-.008Zm0 0v.008h.008v-.008H2.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                        <p class="mt-5 text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-200/90">Investment</p>
                        <p class="mt-1 text-3xl font-semibold tracking-tight text-white sm:text-4xl">R3,500</p>
                        <p class="mt-1 text-sm text-slate-400">Per shooter · entire academy intake</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-slate-950/60 p-5 sm:text-right">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Dates</p>
                        <p class="mt-2 text-xl font-semibold tracking-tight text-white sm:text-2xl">15 August – 19 September</p>
                        <p class="mt-1 text-sm text-slate-400">2026 intake · confirm availability when you enquire</p>
                    </div>
                </div>
            </x-site.card>
        </div>
    </x-site.section>

    {{-- Who this is for --}}
    <x-site.section padding="lg" id="who">
        <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-5">
                <x-site.eyebrow>Fit</x-site.eyebrow>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Who this is for</h2>
                <p class="mt-4 text-slate-400">
                    This is not a casual intro day. If you want honest progression and match-relevant standards, you belong here.
                </p>
            </div>
            <ul class="space-y-4 lg:col-span-7">
                @foreach ([
                    'New shooters who want to get into PRS the right way — with structure, not random range tips.',
                    'Shooters who’ve shot a match and realised they’re not as good as they thought — and want to fix that systematically.',
                    'Anyone serious about becoming match-ready fast, with coaching that respects both the clock and the conditions.',
                ] as $line)
                    <li class="flex gap-4 rounded-2xl border border-white/10 bg-white/[0.02] p-5 ring-1 ring-white/5 sm:p-6">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-brand-400/35 bg-brand-500/10">
                            <svg class="size-5 text-brand-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </span>
                        <span class="text-[15px] leading-relaxed text-slate-200 sm:text-base">{{ $line }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </x-site.section>

    {{-- What you require --}}
    <x-site.section tone="muted" padding="lg" id="require">
        <div class="mx-auto max-w-3xl text-center">
            <x-site.eyebrow>Readiness</x-site.eyebrow>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">What you require</h2>
            <p class="mt-4 text-slate-400">Gear and ammunition baseline so every session stays technical — not a equipment rescue clinic.</p>
        </div>

        <div class="mx-auto mt-12 grid max-w-5xl gap-4 sm:grid-cols-2">
            @foreach ([
                ['Rifle suitable for PRS', '.308 / 6mm / 6.5 Creedmoor — chassis preferred.', 'scope'],
                ['Scope with exposed turrets', 'FFP preferred for consistent holds and corrections.', 'adjust'],
                ['Bipod + rear bag', 'Stable front and rear support for positional work.', 'support'],
                ['Ballistic calculator', 'App or Kestrel — you’ll use it every week.', 'calc'],
                ['Ammunition', '±50 rounds per week for meaningful repetition.', 'ammo'],
            ] as $item)
                @php
                    [$title, $body, $kind] = $item;
                @endphp
                <div class="flex gap-4 rounded-2xl border border-white/10 bg-slate-950/50 p-6 ring-1 ring-white/5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] text-brand-200">
                        @if ($kind === 'scope')
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        @elseif ($kind === 'adjust')
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5h15M4.5 12h15m-15 4.5h15" /></svg>
                        @elseif ($kind === 'support')
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>
                        @elseif ($kind === 'calc')
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25M15.75 3.75H18A2.25 2.25 0 0 1 20.25 6v2.25M15.75 3.75v-1.5M15.75 20.25v1.5M6 3.75H3.75A2.25 2.25 0 0 0 1.5 6v2.25m13.5-4.5v1.5m0 13.5v1.5m-13.5-9v4.5m16.5-4.5v4.5m-16.5 4.5h13.5" /></svg>
                        @else
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z" /></svg>
                        @endif
                    </span>
                    <div>
                        <h3 class="text-base font-semibold text-white">{{ $title }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-400">{{ $body }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </x-site.section>

    {{-- What you get --}}
    <x-site.section padding="lg" id="outcomes">
        <div class="mx-auto max-w-3xl text-center">
            <x-site.eyebrow>Outcomes</x-site.eyebrow>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">What you get</h2>
        </div>

        <div class="mx-auto mt-12 grid max-w-5xl gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Weekly live online coaching', 'Theory, diagnostics, and Q&A with clear action items for the range.'],
                ['Weekly practical range sessions', 'Apply the same week — no “classroom only” drift.'],
                ['Structured progression', 'Fundamentals through to match execution and stage craft.'],
                ['Academy training manual', 'Reference material you can keep when the pressure is on.'],
                ['Real PRS insights', 'What actually matters on the clock — not generic rifle advice.'],
            ] as [$t, $d])
                <x-site.card tone="raised" padding="lg" class="ring-1 ring-white/5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500/15 text-brand-200">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>
                    <h3 class="mt-4 font-semibold text-white">{{ $t }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ $d }}</p>
                </x-site.card>
            @endforeach

            <div class="rounded-2xl border-2 border-brand-400/40 bg-gradient-to-br from-brand-950/50 to-slate-900 p-8 ring-1 ring-brand-400/25 sm:col-span-2 lg:col-span-1">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-brand-200">Outcome</p>
                <p class="mt-3 text-2xl font-semibold leading-tight tracking-tight text-white sm:text-3xl">Match-ready by Week 6</p>
                <p class="mt-4 text-sm leading-relaxed text-slate-300">
                    Full stages, time pressure, and evaluation — built to prepare you for real PRS competition in South Africa.
                </p>
            </div>
        </div>
    </x-site.section>

    {{-- Course structure timeline --}}
    <x-site.section tone="muted" padding="lg" id="structure">
        <div class="mx-auto max-w-3xl text-center">
            <x-site.eyebrow>Syllabus</x-site.eyebrow>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Course structure</h2>
            <p class="mt-4 text-slate-400">
                Weekly format: <span class="text-slate-200">midweek online session</span> ·
                <span class="text-slate-200">weekend practical range session</span>
            </p>
        </div>

        <div class="relative mx-auto mt-14 max-w-3xl">
            <div class="absolute left-[1.125rem] top-3 bottom-3 w-px bg-gradient-to-b from-brand-400/50 via-white/15 to-white/5 sm:left-5" aria-hidden="true"></div>
            <ol class="space-y-10 sm:space-y-12">
                @foreach ([
                    ['Week 1', 'Foundations & Setup', 'Zero, rifle setup, DOPE confirmation'],
                    ['Week 2', 'Shot Process Mastery', 'Consistency, recoil management, impact spotting'],
                    ['Week 3', 'Positional Shooting', 'Barricades, stability vs speed'],
                    ['Week 4', 'Target ID & Stage Planning', 'Stage breakdown and execution'],
                    ['Week 5', 'Wind Reading', 'Practical wind calls and corrections'],
                    ['Week 6', 'Match Simulation', 'Full stages, time pressure, evaluation'],
                ] as [$wk, $title, $desc])
                    <li class="relative flex gap-5 sm:gap-8">
                        <div class="relative z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 border-brand-400/50 bg-slate-950 text-xs font-bold text-brand-100 shadow-[0_0_20px_-4px_rgba(46,151,212,0.5)] sm:h-10 sm:w-10 sm:text-sm">
                            {{ $loop->iteration }}
                        </div>
                        <div class="min-w-0 flex-1 rounded-2xl border border-white/10 bg-slate-950/60 p-5 ring-1 ring-white/5 sm:p-6">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-200/90">{{ $wk }}</p>
                            <h3 class="mt-1 text-lg font-semibold text-white sm:text-xl">{{ $title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-400 sm:text-base">{{ $desc }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </x-site.section>

    {{-- Coaching team placeholder --}}
    <x-site.section padding="default" id="coaches">
        <div class="mx-auto max-w-3xl rounded-2xl border border-white/10 bg-white/[0.02] p-8 text-center ring-1 ring-white/5 sm:p-10">
            <x-site.eyebrow>Instruction</x-site.eyebrow>
            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-white sm:text-3xl">Coaching team</h2>
            <p class="mt-4 text-sm leading-relaxed text-slate-400 sm:text-base">
                PPRC PRS Academy is delivered by experienced precision rifle coaches and active match shooters.
                Named lead instructors and credentials will be published here ahead of the intake.
            </p>
        </div>
    </x-site.section>

    {{-- FAQ --}}
    <x-site.section tone="muted" padding="lg" id="faq">
        <x-site.faq-group
            eyebrow="Academy"
            title="Common questions"
            description="Straight answers — if yours isn’t here, use the enquiry form."
        >
            <x-site.faq-item question="Is this only for Pretoria shooters?">
                <p>
                    PPRC is based in Pretoria, Gauteng. Range days are scheduled for local venues; online sessions work
                    anywhere with a stable connection. If you’re unsure about travel for range weekends, ask when you enquire.
                </p>
            </x-site.faq-item>
            <x-site.faq-item question="Do I need prior match experience?">
                <p>
                    No — the academy is built for serious beginners. If you’ve already shot a match, you’ll still benefit:
                    we replace guesswork with a structured shot process and stage approach.
                </p>
            </x-site.faq-item>
            <x-site.faq-item question="What happens after Week 6?">
                <p>
                    You should be able to enter a club or provincial PRS match with a clear plan for zero, DOPE, positions,
                    wind, and stage execution. PPRC match calendars are published on this site when registrations open.
                </p>
            </x-site.faq-item>
            <x-site.faq-item question="Is equipment included?">
                <p>
                    No. You bring a match-suitable rifle, optic, supports, solver, and ammunition. That keeps every session
                    focused on performance, not kit logistics.
                </p>
            </x-site.faq-item>
        </x-site.faq-group>
    </x-site.section>

    {{-- Final CTA --}}
    <x-site.section padding="lg" id="enquire">
        <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-slate-900 via-slate-950 to-brand-950/40 p-8 ring-1 ring-white/10 sm:p-12 lg:p-14">
            <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-brand-500/20 blur-3xl" aria-hidden="true"></div>
            <div class="relative mx-auto max-w-3xl text-center">
                <h2 class="text-3xl font-semibold leading-tight tracking-tight text-white sm:text-4xl lg:text-[2.75rem] lg:leading-[1.1]">
                    Stop guessing. Start shooting with intent.
                </h2>
                <p class="mt-6 text-base leading-relaxed text-slate-300 sm:text-lg">
                    Limited intake. Structured coaching. Serious progression — built to move you from fundamentals to
                    match execution under real PRS pressure.
                </p>
                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <x-site.button href="{{ $contactApply }}" variant="primary" size="lg" class="min-w-[220px] shadow-lg shadow-black/30">
                        Apply / Enquire Now
                    </x-site.button>
                    <x-site.button href="{{ $contactGeneral }}" variant="secondary" size="lg" class="min-w-[220px]">
                        Contact Us
                    </x-site.button>
                </div>
            </div>
        </div>
    </x-site.section>

    {{-- SEO-supporting copy: natural keywords, low visual weight --}}
    <x-site.section padding="sm" tone="base">
        <div class="mx-auto max-w-3xl border-t border-white/5 pt-10 text-center">
            <p class="text-sm leading-relaxed text-slate-500">
                PPRC PRS Academy is precision rifle training for South Africa’s practical precision rifle community —
                beginner PRS training with a match focus, combining PRS academy–style structure with Pretoria range time.
                For PRS training in South Africa and Gauteng-based precision rifle coaching, enquire for the next intake.
            </p>
        </div>
    </x-site.section>

    {{-- Mobile sticky CTA (hidden on large screens) --}}
    <div
        class="fixed inset-x-0 bottom-0 z-50 border-t border-white/10 bg-slate-950/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur-md lg:hidden"
        role="region"
        aria-label="Academy actions"
    >
        <div class="mx-auto flex max-w-lg gap-2">
            <x-site.button href="{{ $contactApply }}" variant="primary" size="sm" class="min-w-0 flex-1 justify-center">
                Apply / Enquire
            </x-site.button>
            <x-site.button href="#program" variant="secondary" size="sm" class="min-w-0 flex-1 justify-center">
                Program
            </x-site.button>
        </div>
    </div>
</div>
</x-site.layout>
