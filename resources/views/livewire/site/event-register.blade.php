@php
    /** @var \App\Models\Event $event */
    $state = $event->registrationState();
    $isOpen = $state === \App\Enums\RegistrationState::Open;
@endphp

<div class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/[0.07] to-white/[0.02] p-5 shadow-[0_20px_50px_-24px_rgba(0,0,0,0.65)] sm:p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-white sm:text-2xl">Enter this match</h2>
            @if ($isOpen)
                <p class="mt-1 max-w-xl text-sm text-slate-400">
                    Members signed in with a verified email can register in one step. Guests confirm by email code so we keep spam out of the squad list.
                </p>
            @endif
        </div>
        @if ($this->alreadyRegistered)
            <span class="inline-flex shrink-0 items-center rounded-full border border-emerald-400/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-emerald-200">
                You are on the list
            </span>
        @endif
    </div>

    {{-- When the match isn't accepting entries, replace the whole form with a
         state-specific panel. The panels never surface an entry button or any
         "enter below" wording so the public page can't accidentally invite
         registrations the server would reject anyway. --}}
    @unless ($isOpen)
        @php
            $panelClass = match ($state) {
                \App\Enums\RegistrationState::Closed     => 'border-slate-400/30 bg-slate-500/10 text-slate-200',
                \App\Enums\RegistrationState::Full       => 'border-amber-400/40 bg-amber-500/10 text-amber-100',
                \App\Enums\RegistrationState::NotYetOpen => 'border-sky-400/30 bg-sky-500/10 text-sky-100',
                \App\Enums\RegistrationState::Finished   => 'border-slate-400/30 bg-slate-500/10 text-slate-200',
            };
            $panelHeading = $state->label();
        @endphp
        <div role="status" aria-live="polite" class="mt-6 rounded-xl border px-5 py-5 {{ $panelClass }}">
            <p class="text-base font-semibold text-white">{{ $panelHeading }}</p>

            @switch($state)
                @case(\App\Enums\RegistrationState::Closed)
                    <p class="mt-1.5 text-sm">
                        Entries for this match have closed so the organisers can finalise squad lists and match planning.
                        @if ($event->registrations_close_at)
                            Entries closed on {{ $event->registrations_close_at->format('D, d M Y') }}
                            @if ($event->registrations_close_at->format('H:i') !== '00:00')
                                at {{ $event->registrations_close_at->format('H:i') }}
                            @endif.
                        @endif
                    </p>
                    <p class="mt-2 text-xs">
                        Have a question about the match? <a href="{{ url('/contact') }}" class="font-medium text-white underline underline-offset-2 hover:no-underline">Contact the club</a>.
                    </p>
                    @break

                @case(\App\Enums\RegistrationState::Full)
                    <p class="mt-1.5 text-sm">
                        Every place has been taken. We can't accept further entries for this match.
                    </p>
                    <p class="mt-2 text-xs">
                        Need to reach the organisers? <a href="{{ url('/contact') }}" class="font-medium text-white underline underline-offset-2 hover:no-underline">Contact the club</a>.
                    </p>
                    @break

                @case(\App\Enums\RegistrationState::NotYetOpen)
                    <p class="mt-1.5 text-sm">
                        @if ($event->registrations_open_at)
                            Registration opens on {{ $event->registrations_open_at->format('D, d M Y') }} at {{ $event->registrations_open_at->format('H:i') }}. Check back then.
                        @else
                            Registration isn't open yet. Check back soon.
                        @endif
                    </p>
                    @break

                @case(\App\Enums\RegistrationState::Finished)
                    <p class="mt-1.5 text-sm">
                        This match has already taken place.
                    </p>
                    @break
            @endswitch
        </div>
    @endunless

    @if ($isOpen)
    {{-- Non-members commonly misread this page and start a club-membership
         application thinking they need one to shoot the match. The guest form
         below is all they actually need, so we spell that out up front for
         anyone who isn't signed in. `auth()->check()` (not @@guest) because
         the directive doesn't fire reliably inside Livewire's test render
         context. --}}
    @if (! auth()->check())
        <div class="mt-4 rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100" dusk="guest-welcome-banner">
            <p class="font-semibold text-white">You don't need a PPRC membership to shoot this match.</p>
            <p class="mt-0.5 text-emerald-200/90">
                Guests are welcome. Scroll down to <strong>Guests &amp; visitors</strong> and enter with your name, email and (if asked) your division.
            </p>
        </div>
    @endif

    @if ($toast)
        <div wire:key="toast-{{ md5($toast) }}" role="status" aria-live="polite" class="mt-4 rounded-xl border border-emerald-400/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
            {{ $toast }}
        </div>
    @endif

    {{-- "How entry works" — small three-step summary of the real process so
         first-time visitors know what to expect. Shown only when the form is
         available (open and not already registered). --}}
    @php
        $hasHousehold = auth()->check() && $this->member && $this->householdMembers->count() > 1;
        $hideMemberFlow = $this->alreadyRegistered && ! $hasHousehold;
    @endphp

    @unless ($hideMemberFlow)
        <ol class="mt-5 grid gap-3 text-xs text-slate-400 sm:grid-cols-3" aria-label="How entry works">
            <li class="rounded-lg border border-white/5 bg-slate-950/40 px-3 py-2.5">
                <span class="block text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Step 1</span>
                <span class="mt-1 block text-slate-200">
                    @if (auth()->check())
                        Fill in your match options and click <strong>Enter me</strong>.
                    @else
                        Guests get a 6-digit code by email to confirm you're real.
                    @endif
                </span>
            </li>
            <li class="rounded-lg border border-white/5 bg-slate-950/40 px-3 py-2.5">
                <span class="block text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Step 2</span>
                <span class="mt-1 block text-slate-200">
                    @if ($event->is_saprf_match)
                        Pay by EFT using your unique reference, or (SAPRF entries) pay through the SAPRF portal.
                    @else
                        Pay by EFT. We email you the amount, banking details and a unique reference the moment you register.
                    @endif
                </span>
            </li>
            <li class="rounded-lg border border-white/5 bg-slate-950/40 px-3 py-2.5">
                <span class="block text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Step 3</span>
                <span class="mt-1 block text-slate-200">
                    Your entry shows <span class="font-semibold text-emerald-300">Confirmed</span> once the club marks the payment received.
                    @auth
                        Upload proof under <a href="{{ url('/portal') }}" class="underline underline-offset-2 hover:text-white">My Registrations</a> if you want to speed it up.
                    @endauth
                </span>
            </li>
        </ol>
    @endunless

    @if ($hideMemberFlow)
        <p class="mt-6 text-sm text-slate-300">
            If you need to change your entry, contact the match director.
        </p>
    @else
        <div class="mt-6 grid gap-6 lg:grid-cols-2 lg:gap-8">
            {{-- Member path --}}
            <div class="rounded-xl border border-brand-400/20 bg-slate-950/40 p-5">
                <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-brand-200">Members</h3>
                @auth
                    @if (! auth()->user()->hasVerifiedEmail())
                        <p class="mt-3 text-sm text-amber-200/90">
                            Verify your email (check your inbox for the club PIN) before you can register for matches.
                        </p>
                    @elseif (! $this->member)
                        <p class="mt-3 text-sm text-slate-400">
                            Your login does not have a member profile yet. Use the guest path or contact the membership secretary.
                        </p>
                    @else
                        @error('register')
                            <p class="mt-3 text-sm text-red-300" role="alert">{{ $message }}</p>
                        @enderror

                        @php $household = $this->householdMembers; @endphp
                        @if ($household->count() > 1)
                            <p class="mt-3 text-sm text-slate-300">
                                <span class="font-medium text-white">Who is shooting?</span>
                                <span class="text-xs text-slate-500 block mt-0.5">Paying from your account. Proof goes under My Registrations.</span>
                            </p>
                            <ul class="mt-3 divide-y divide-white/5 rounded-xl border border-white/10 bg-slate-950/40" dusk="household-picker" aria-label="People in your household">
                                @foreach ($household as $entry)
                                    @php
                                        $m = $entry->member;
                                        $isSelf = $m->id === $this->member->id;
                                        $fee = $entry->fee_cents;
                                    @endphp
                                    <li wire:key="hh-{{ $m->id }}" dusk="household-row-{{ $m->id }}" class="flex flex-wrap items-center gap-3 px-4 py-3">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-white">
                                                {{ $isSelf ? 'You — '.$m->fullName() : $m->fullName() }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                @if ($m->isJunior())
                                                    Junior
                                                @elseif ($m->currentMembership())
                                                    {{ $m->currentMembership()->membership_type_name_snapshot }}
                                                @endif
                                                @if ($fee !== null)
                                                    · @if ($viaSaprf && $isSelf)
                                                        <span class="font-semibold text-amber-200">Free (SAPRF)</span>
                                                    @else
                                                        <span class="tabular-nums text-slate-300">R {{ number_format($fee / 100, 2) }}</span>
                                                    @endif
                                                @endif
                                            </p>
                                        </div>
                                        @if ($entry->entered)
                                            <span class="inline-flex items-center rounded-full border border-emerald-400/30 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-emerald-200">
                                                Entered
                                            </span>
                                        @else
                                            <button type="button" wire:click="registerMemberFor({{ $m->id }})" wire:loading.attr="disabled" wire:target="registerMemberFor({{ $m->id }})"
                                                dusk="household-enter-{{ $m->id }}"
                                                class="btn-brand inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold text-white shadow transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-40">
                                                <span wire:loading.remove wire:target="registerMemberFor({{ $m->id }})">
                                                    {{ $isSelf ? 'Enter me' : 'Enter '.$m->first_name }}
                                                </span>
                                                <span wire:loading wire:target="registerMemberFor({{ $m->id }})" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                            </button>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <p class="mt-3 text-sm text-slate-300 {{ $household->count() > 1 ? 'hidden' : '' }}">
                            Register as <span class="font-medium text-white">{{ $this->member->fullName() }}</span>
                            @php $fee = $event->effectivePriceCentsFor($this->member); @endphp
                            @if ($fee !== null)
                                <span class="text-slate-500"> · </span>
                                @if ($viaSaprf)
                                    <span class="font-semibold text-amber-200">Free</span>
                                    <span class="text-slate-500">(paid via SAPRF)</span>
                                @else
                                    <span class="tabular-nums text-brand-100">R {{ number_format($fee / 100, 2) }}</span>
                                    <span class="text-slate-500">entry</span>
                                @endif
                            @endif
                        </p>
                        @if ($event->is_saprf_match)
                            <fieldset class="mt-4 rounded-lg border border-amber-400/30 bg-amber-500/5 p-4">
                                <legend class="px-2 text-xs font-semibold uppercase tracking-[0.18em] text-amber-200">SAPRF entry</legend>
                                <label class="flex items-start gap-3 text-sm text-slate-200 cursor-pointer">
                                    <input type="checkbox" wire:model.live="viaSaprf" class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-amber-500 focus:ring-amber-500/40" />
                                    <span>
                                        <span class="font-medium text-white">I am entering through SAPRF</span>
                                        <span class="block mt-0.5 text-xs text-slate-400">PPRC entry fee waived. Pay via the SAPRF portal.</span>
                                    </span>
                                </label>
                                @if ($viaSaprf)
                                    <div class="mt-3">
                                        <label for="member-saprf-number" class="text-xs font-medium uppercase tracking-wider text-slate-500">SAPRF membership # <span class="text-slate-500">(optional)</span></label>
                                        <input id="member-saprf-number" type="text" wire:model="saprfNumber" placeholder="e.g. SAPRF-1234" autocomplete="off"
                                            class="mt-1.5 w-full rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-white focus:border-amber-400/50 focus:outline-none focus:ring-2 focus:ring-amber-500/30" />
                                    </div>
                                @endif
                            </fieldset>
                        @endif
                        @php $regSelectClass = 'mt-1.5 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-2.5 text-sm text-white focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30'; @endphp
                        @if ($event->offersBothCourses())
                            <fieldset class="mt-4 space-y-3 border-t border-white/10 pt-4">
                                <legend class="text-xs font-medium uppercase tracking-wider text-slate-500">Course of fire</legend>
                                <div>
                                    <label for="member-course" class="sr-only">Course of fire</label>
                                    <select id="member-course" wire:model.live="course" required aria-required="true"
                                        @error('course') aria-invalid="true" aria-describedby="member-course-error" @enderror
                                        class="{{ $regSelectClass }}">
                                        <option value="">Select…</option>
                                        <option value="full">SAPRF Provincial — {{ $event->roundsForCourse('full') }} rounds</option>
                                        <option value="club">PPRC club match — {{ $event->roundsForCourse('club') }} rounds</option>
                                    </select>
                                    @error('course') <p id="member-course-error" class="mt-1 text-xs text-red-300" role="alert">{{ $message }}</p> @enderror
                                </div>
                            </fieldset>
                        @endif
                        @if ($event->collectsDivisionAtRegistration() || $event->collectsCategoryAtRegistration())
                            <fieldset class="mt-4 space-y-3 border-t border-white/10 pt-4">
                                <legend class="text-xs font-medium uppercase tracking-wider text-slate-500">Division &amp; category</legend>
                                @if ($event->collectsDivisionAtRegistration())
                                    <div>
                                        <label for="member-division" class="text-xs font-medium uppercase tracking-wider text-slate-500">Division</label>
                                        <select id="member-division" wire:model="division" required aria-required="true"
                                            @error('division') aria-invalid="true" aria-describedby="member-division-error" @enderror
                                            class="{{ $regSelectClass }}">
                                            <option value="">Select…</option>
                                            @foreach ($event->registrationDivisionChoices() as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select>
                                        @error('division') <p id="member-division-error" class="mt-1 text-xs text-red-300" role="alert">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                                @if ($event->collectsCategoryAtRegistration())
                                    <div>
                                        <label for="member-category" class="text-xs font-medium uppercase tracking-wider text-slate-500">Category</label>
                                        <select id="member-category" wire:model="category" required aria-required="true"
                                            @error('category') aria-invalid="true" aria-describedby="member-category-error" @enderror
                                            class="{{ $regSelectClass }}">
                                            <option value="">Select…</option>
                                            @foreach ($event->registrationCategoryChoices() as $c)
                                                <option value="{{ $c }}">{{ $c }}</option>
                                            @endforeach
                                        </select>
                                        @error('category') <p id="member-category-error" class="mt-1 text-xs text-red-300" role="alert">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </fieldset>
                        @endif
                        @if ($household->count() <= 1)
                            <button
                                type="button"
                                wire:click="registerMember"
                                wire:loading.attr="disabled"
                                class="btn-brand mt-5 inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                <span wire:loading.remove wire:target="registerMember">Register me</span>
                                <span wire:loading wire:target="registerMember" class="inline-flex items-center gap-2">
                                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                    Saving…
                                </span>
                            </button>
                        @endif
                    @endif
                @else
                    {{-- Logged-out visitors land here. Keep the copy focused on
                         people who ALREADY have a PPRC account — the "join the
                         club" link used to live here as a brand-coloured CTA
                         and was the single biggest source of half-finished
                         membership applications from people who really just
                         wanted to shoot a match. The Sign-in button goes via
                         matches.sign-in so the login handoff parks the match
                         URL as `url.intended` and the member lands back here. --}}
                    <p class="mt-3 text-sm text-slate-400">
                        <span class="font-medium text-white">Already a PPRC member with an account?</span>
                        Sign in for member pricing and one-tap entry for yourself and anyone linked to your household.
                    </p>
                    <a
                        href="{{ route('matches.sign-in', ['event' => $event->slug]) }}"
                        class="btn-brand mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:brightness-110 sm:w-auto"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        Sign in to register
                    </a>
                    <p class="mt-3 text-xs text-slate-500">
                        Not a member? <strong class="text-slate-400">Don't start a membership just to shoot this match</strong> — use the guest form on the right. Shooting regularly? You can <a href="{{ url('/membership') }}" class="text-slate-400 underline decoration-slate-700 underline-offset-2 hover:text-slate-200">join as a member</a> later.
                    </p>
                @endauth
            </div>

            {{-- Guest path --}}
            <div class="rounded-xl border border-white/10 bg-slate-950/30 p-5">
                <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Guests &amp; visitors</h3>
                @if (! auth()->check())
                    <p class="mt-2 text-xs text-emerald-300/80">
                        No membership required. Enter below.
                    </p>
                @endif

                @if ($guestStep === 'pin')
                    <p class="mt-3 text-sm text-slate-300">
                        Enter the 6-digit code we sent to <span class="font-medium text-white">{{ $guestEmail }}</span>. The code is valid for 15 minutes.
                    </p>
                    <div class="mt-4 space-y-3">
                        <label for="guest-pin" class="block text-xs font-medium uppercase tracking-wider text-slate-500">Code</label>
                        <input
                            id="guest-pin"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            wire:model="pin"
                            autocomplete="one-time-code"
                            required
                            aria-required="true"
                            @error('pin') aria-invalid="true" aria-describedby="guest-pin-error" @enderror
                            class="w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-3 text-center font-mono text-2xl tracking-[0.35em] text-white placeholder:text-slate-600 focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                            placeholder="000000"
                        />
                        @error('pin')
                            <p id="guest-pin-error" class="text-sm text-red-300" role="alert">{{ $message }}</p>
                        @enderror
                        <div class="flex flex-wrap gap-3">
                            <button
                                type="button"
                                wire:click="confirmGuestPin"
                                wire:loading.attr="disabled"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-100 sm:flex-none"
                            >
                                <span wire:loading.remove wire:target="confirmGuestPin">Confirm &amp; register</span>
                                <span wire:loading wire:target="confirmGuestPin" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-400 border-t-slate-900"></span>
                            </button>
                            <button type="button" wire:click="$set('guestStep', 'guest')" class="text-sm text-slate-500 hover:text-slate-300">
                                Start over
                            </button>
                        </div>
                    </div>
                @elseif ($guestStep === 'done')
                    <p class="mt-4 text-sm font-medium text-emerald-200">You are registered. Safe travels — we will see you at the range.</p>
                @else
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="guest-name" class="text-xs font-medium uppercase tracking-wider text-slate-500">Full name <span class="text-rose-400" aria-hidden="true">*</span></label>
                            <input id="guest-name" type="text" wire:model="guestName"
                                autocomplete="name" required aria-required="true"
                                @error('guestName') aria-invalid="true" aria-describedby="guest-name-error" @enderror
                                class="mt-1.5 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-2.5 text-sm text-white focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30" />
                            @error('guestName') <p id="guest-name-error" class="mt-1 text-xs text-red-300" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="guest-email" class="text-xs font-medium uppercase tracking-wider text-slate-500">Email <span class="text-rose-400" aria-hidden="true">*</span></label>
                            <input id="guest-email" type="email" wire:model="guestEmail"
                                autocomplete="email" inputmode="email" required aria-required="true"
                                aria-describedby="guest-email-help @error('guestEmail') guest-email-error @enderror"
                                @error('guestEmail') aria-invalid="true" @enderror
                                class="mt-1.5 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-2.5 text-sm text-white focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30" />
                            <p id="guest-email-help" class="mt-1 text-xs text-slate-500">We'll send a 6-digit code to this address so we can prove it's really you.</p>
                            @error('guestEmail') <p id="guest-email-error" class="mt-1 text-xs text-red-300" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="guest-phone" class="text-xs font-medium uppercase tracking-wider text-slate-500">Phone <span class="text-slate-500">(optional)</span></label>
                            <input id="guest-phone" type="tel" wire:model="guestPhone"
                                autocomplete="tel" inputmode="tel"
                                @error('guestPhone') aria-invalid="true" aria-describedby="guest-phone-error" @enderror
                                class="mt-1.5 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-2.5 text-sm text-white focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30" />
                            @error('guestPhone') <p id="guest-phone-error" class="mt-1 text-xs text-red-300" role="alert">{{ $message }}</p> @enderror
                        </div>
                        @php $regSelectClass = 'mt-1.5 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-2.5 text-sm text-white focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30'; @endphp
                        @if ($event->offersBothCourses())
                            <fieldset class="space-y-3 border-t border-white/10 pt-4">
                                <legend class="text-xs font-medium uppercase tracking-wider text-slate-500">Course of fire</legend>
                                <div>
                                    <label for="guest-course" class="sr-only">Course of fire</label>
                                    <select id="guest-course" wire:model.live="course" required aria-required="true"
                                        @error('course') aria-invalid="true" aria-describedby="guest-course-error" @enderror
                                        class="{{ $regSelectClass }}">
                                        <option value="">Select…</option>
                                        <option value="full">SAPRF Provincial — {{ $event->roundsForCourse('full') }} rounds</option>
                                        <option value="club">PPRC club match — {{ $event->roundsForCourse('club') }} rounds</option>
                                    </select>
                                    @error('course') <p id="guest-course-error" class="mt-1 text-xs text-red-300" role="alert">{{ $message }}</p> @enderror
                                </div>
                            </fieldset>
                        @endif
                        @if ($event->collectsDivisionAtRegistration() || $event->collectsCategoryAtRegistration())
                            <fieldset class="space-y-3 border-t border-white/10 pt-4">
                                <legend class="text-xs font-medium uppercase tracking-wider text-slate-500">Division &amp; category</legend>
                                @if ($event->collectsDivisionAtRegistration())
                                    <div>
                                        <label for="guest-division" class="text-xs font-medium uppercase tracking-wider text-slate-500">Division</label>
                                        <select id="guest-division" wire:model="division" required aria-required="true"
                                            @error('division') aria-invalid="true" aria-describedby="guest-division-error" @enderror
                                            class="{{ $regSelectClass }}">
                                            <option value="">Select…</option>
                                            @foreach ($event->registrationDivisionChoices() as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select>
                                        @error('division') <p id="guest-division-error" class="mt-1 text-xs text-red-300" role="alert">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                                @if ($event->collectsCategoryAtRegistration())
                                    <div>
                                        <label for="guest-category" class="text-xs font-medium uppercase tracking-wider text-slate-500">Category</label>
                                        <select id="guest-category" wire:model="category" required aria-required="true"
                                            @error('category') aria-invalid="true" aria-describedby="guest-category-error" @enderror
                                            class="{{ $regSelectClass }}">
                                            <option value="">Select…</option>
                                            @foreach ($event->registrationCategoryChoices() as $c)
                                                <option value="{{ $c }}">{{ $c }}</option>
                                            @endforeach
                                        </select>
                                        @error('category') <p id="guest-category-error" class="mt-1 text-xs text-red-300" role="alert">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </fieldset>
                        @endif
                        @if ($event->is_saprf_match)
                            <fieldset class="rounded-lg border border-amber-400/30 bg-amber-500/5 p-4">
                                <legend class="px-2 text-xs font-semibold uppercase tracking-[0.18em] text-amber-200">SAPRF entry</legend>
                                <label class="flex items-start gap-3 text-sm text-slate-200 cursor-pointer">
                                    <input type="checkbox" wire:model.live="viaSaprf" class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-amber-500 focus:ring-amber-500/40" />
                                    <span>
                                        <span class="font-medium text-white">I am a SAPRF member entering through SAPRF</span>
                                        <span class="block mt-0.5 text-xs text-slate-400">No PPRC entry fee. Pay via the SAPRF portal.</span>
                                    </span>
                                </label>
                                @if ($viaSaprf)
                                    <div class="mt-3">
                                        <label for="guest-saprf-number" class="text-xs font-medium uppercase tracking-wider text-slate-500">SAPRF membership # <span class="text-slate-500">(optional)</span></label>
                                        <input id="guest-saprf-number" type="text" wire:model="saprfNumber" placeholder="e.g. SAPRF-1234" autocomplete="off"
                                            class="mt-1.5 w-full rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-white focus:border-amber-400/50 focus:outline-none focus:ring-2 focus:ring-amber-500/30" />
                                    </div>
                                @endif
                            </fieldset>
                        @endif

                        @if ($this->canFlagJunior() && ! $viaSaprf)
                            <label for="guest-junior" class="flex items-start gap-3 rounded-lg border border-white/10 bg-slate-950/40 p-4 text-sm text-slate-200 cursor-pointer">
                                <input id="guest-junior" type="checkbox" wire:model.live="isJunior" class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-brand-500 focus:ring-brand-500/40" />
                                <span>
                                    <span class="font-medium text-white">Junior shooter (under 18)</span>
                                    <span class="block mt-0.5 text-xs text-slate-400">Junior entry fee applies.</span>
                                </span>
                            </label>
                        @endif

                        @php $guestFee = $event->effectivePriceCentsFor(null, $this->canFlagJunior() && $isJunior); @endphp
                        @if ($guestFee !== null)
                            <p class="text-sm text-slate-400">
                                Typical guest entry:
                                @if ($viaSaprf)
                                    <span class="font-semibold text-amber-200">Free</span>
                                    <span class="text-slate-500">(paid via SAPRF)</span>
                                @else
                                    <span class="font-semibold tabular-nums text-white">R {{ number_format($guestFee / 100, 2) }}</span>
                                    @if ($this->canFlagJunior() && $isJunior)
                                        <span class="text-slate-500">(junior rate)</span>
                                    @else
                                        <span class="text-slate-500">(collected per club payment rules)</span>
                                    @endif
                                @endif
                            </p>
                        @endif
                        <button
                            type="button"
                            wire:click="sendGuestPin"
                            wire:loading.attr="disabled"
                            class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-brand-500 disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
                        >
                            <span wire:loading.remove wire:target="sendGuestPin">Email me a code &amp; continue</span>
                            <span wire:loading wire:target="sendGuestPin" class="inline-flex items-center justify-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                Sending…
                            </span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
    @endif {{-- /isOpen --}}
</div>
