<div class="rounded-2xl border border-white/10 bg-gradient-to-br from-white/[0.07] to-white/[0.02] p-5 shadow-[0_20px_50px_-24px_rgba(0,0,0,0.65)] sm:p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-white sm:text-2xl">Enter this match</h2>
            <p class="mt-1 max-w-xl text-sm text-slate-400">
                Members signed in with a verified email can register in one step. Guests confirm by email code so we keep spam out of the squad list.
            </p>
        </div>
        @if ($this->alreadyRegistered)
            <span class="inline-flex shrink-0 items-center rounded-full border border-emerald-400/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-emerald-200">
                You are on the list
            </span>
        @endif
    </div>

    @if ($toast)
        <div wire:key="toast-{{ md5($toast) }}" class="mt-4 rounded-xl border border-emerald-400/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
            {{ $toast }}
        </div>
    @endif

    @if ($this->alreadyRegistered)
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
                            <p class="mt-3 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                        <p class="mt-3 text-sm text-slate-300">
                            Register as <span class="font-medium text-white">{{ $this->member->fullName() }}</span>
                            @php($fee = $event->effectivePriceCentsFor($this->member))
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
                            <div class="mt-4 rounded-lg border border-amber-400/30 bg-amber-500/5 p-4">
                                <label class="flex items-start gap-3 text-sm text-slate-200 cursor-pointer">
                                    <input type="checkbox" wire:model.live="viaSaprf" class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-amber-500 focus:ring-amber-500/40" />
                                    <span>
                                        <span class="font-medium text-white">I am entering through SAPRF</span>
                                        <span class="block mt-0.5 text-xs text-slate-400">PPRC entry fee waived. Pay via the SAPRF portal.</span>
                                    </span>
                                </label>
                                @if ($viaSaprf)
                                    <div class="mt-3">
                                        <label class="text-xs font-medium uppercase tracking-wider text-slate-500">SAPRF membership # (optional)</label>
                                        <input type="text" wire:model="saprfNumber" placeholder="e.g. SAPRF-1234"
                                            class="mt-1.5 w-full rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-white focus:border-amber-400/50 focus:outline-none focus:ring-2 focus:ring-amber-500/30" />
                                    </div>
                                @endif
                            </div>
                        @endif
                        @if ($event->collectsDivisionAtRegistration() || $event->collectsCategoryAtRegistration())
                            @php($regSelectClass = 'mt-1.5 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-2.5 text-sm text-white focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30')
                            <div class="mt-4 space-y-3 border-t border-white/10 pt-4">
                                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Division &amp; category</p>
                                @if ($event->collectsDivisionAtRegistration())
                                    <div>
                                        <label class="text-xs font-medium uppercase tracking-wider text-slate-500">Division</label>
                                        <select wire:model="division" @required($event->collectsDivisionAtRegistration()) class="{{ $regSelectClass }}">
                                            <option value="">Select…</option>
                                            @foreach ($event->registrationDivisionChoices() as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select>
                                        @error('division') <p class="mt-1 text-xs text-red-300">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                                @if ($event->collectsCategoryAtRegistration())
                                    <div>
                                        <label class="text-xs font-medium uppercase tracking-wider text-slate-500">Category</label>
                                        <select wire:model="category" @required($event->collectsCategoryAtRegistration()) class="{{ $regSelectClass }}">
                                            <option value="">Select…</option>
                                            @foreach ($event->registrationCategoryChoices() as $c)
                                                <option value="{{ $c }}">{{ $c }}</option>
                                            @endforeach
                                        </select>
                                        @error('category') <p class="mt-1 text-xs text-red-300">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </div>
                        @endif
                        <button
                            type="button"
                            wire:click="registerMember"
                            wire:loading.attr="disabled"
                            @disabled(! $event->isRegistrationOpen())
                            class="btn-brand mt-5 inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <span wire:loading.remove wire:target="registerMember">Register me</span>
                            <span wire:loading wire:target="registerMember" class="inline-flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                Saving…
                            </span>
                        </button>
                        @if (! $event->isRegistrationOpen())
                            <p class="mt-2 text-xs text-slate-500">Registrations are closed or full.</p>
                        @endif
                    @endif
                @else
                    <p class="mt-3 text-sm text-slate-400">
                        <a href="{{ url('/login') }}" class="font-medium text-brand-300 hover:text-brand-200">Sign in</a>
                        with a verified account for member pricing and one-tap entry.
                    </p>
                @endauth
            </div>

            {{-- Guest path --}}
            <div class="rounded-xl border border-white/10 bg-slate-950/30 p-5">
                <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-400">Guests & visitors</h3>

                @if ($guestStep === 'pin')
                    <p class="mt-3 text-sm text-slate-300">
                        Enter the 6-digit code we sent to <span class="font-medium text-white">{{ $guestEmail }}</span>.
                    </p>
                    <div class="mt-4 space-y-3">
                        <label class="block text-xs font-medium uppercase tracking-wider text-slate-500">Code</label>
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            wire:model="pin"
                            class="w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-3 text-center font-mono text-2xl tracking-[0.35em] text-white placeholder:text-slate-600 focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                            placeholder="000000"
                            autocomplete="one-time-code"
                        />
                        @error('pin')
                            <p class="text-sm text-red-300">{{ $message }}</p>
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
                            <label class="text-xs font-medium uppercase tracking-wider text-slate-500">Full name</label>
                            <input type="text" wire:model="guestName" class="mt-1.5 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-2.5 text-sm text-white focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30" />
                            @error('guestName') <p class="mt-1 text-xs text-red-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-medium uppercase tracking-wider text-slate-500">Email</label>
                            <input type="email" wire:model="guestEmail" class="mt-1.5 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-2.5 text-sm text-white focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30" />
                            @error('guestEmail') <p class="mt-1 text-xs text-red-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-medium uppercase tracking-wider text-slate-500">Phone (optional)</label>
                            <input type="tel" wire:model="guestPhone" class="mt-1.5 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-2.5 text-sm text-white focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30" />
                            @error('guestPhone') <p class="mt-1 text-xs text-red-300">{{ $message }}</p> @enderror
                        </div>
                        @if ($event->collectsDivisionAtRegistration() || $event->collectsCategoryAtRegistration())
                            @php($regSelectClass = 'mt-1.5 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-2.5 text-sm text-white focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30')
                            <div class="space-y-3 border-t border-white/10 pt-4">
                                <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Division &amp; category</p>
                                @if ($event->collectsDivisionAtRegistration())
                                    <div>
                                        <label class="text-xs font-medium uppercase tracking-wider text-slate-500">Division</label>
                                        <select wire:model="division" @required($event->collectsDivisionAtRegistration()) class="{{ $regSelectClass }}">
                                            <option value="">Select…</option>
                                            @foreach ($event->registrationDivisionChoices() as $d)
                                                <option value="{{ $d }}">{{ $d }}</option>
                                            @endforeach
                                        </select>
                                        @error('division') <p class="mt-1 text-xs text-red-300">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                                @if ($event->collectsCategoryAtRegistration())
                                    <div>
                                        <label class="text-xs font-medium uppercase tracking-wider text-slate-500">Category</label>
                                        <select wire:model="category" @required($event->collectsCategoryAtRegistration()) class="{{ $regSelectClass }}">
                                            <option value="">Select…</option>
                                            @foreach ($event->registrationCategoryChoices() as $c)
                                                <option value="{{ $c }}">{{ $c }}</option>
                                            @endforeach
                                        </select>
                                        @error('category') <p class="mt-1 text-xs text-red-300">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </div>
                        @endif
                        @if ($event->is_saprf_match)
                            <div class="rounded-lg border border-amber-400/30 bg-amber-500/5 p-4">
                                <label class="flex items-start gap-3 text-sm text-slate-200 cursor-pointer">
                                    <input type="checkbox" wire:model.live="viaSaprf" class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-amber-500 focus:ring-amber-500/40" />
                                    <span>
                                        <span class="font-medium text-white">I am a SAPRF member entering through SAPRF</span>
                                        <span class="block mt-0.5 text-xs text-slate-400">No PPRC entry fee. Pay via the SAPRF portal.</span>
                                    </span>
                                </label>
                                @if ($viaSaprf)
                                    <div class="mt-3">
                                        <label class="text-xs font-medium uppercase tracking-wider text-slate-500">SAPRF membership # (optional)</label>
                                        <input type="text" wire:model="saprfNumber" placeholder="e.g. SAPRF-1234"
                                            class="mt-1.5 w-full rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-white focus:border-amber-400/50 focus:outline-none focus:ring-2 focus:ring-amber-500/30" />
                                    </div>
                                @endif
                            </div>
                        @endif

                        @php($guestFee = $event->effectivePriceCentsFor(null))
                        @if ($guestFee !== null)
                            <p class="text-sm text-slate-400">
                                Typical guest entry:
                                @if ($viaSaprf)
                                    <span class="font-semibold text-amber-200">Free</span>
                                    <span class="text-slate-500">(paid via SAPRF)</span>
                                @else
                                    <span class="font-semibold tabular-nums text-white">R {{ number_format($guestFee / 100, 2) }}</span>
                                    <span class="text-slate-500">(collected per club payment rules)</span>
                                @endif
                            </p>
                        @endif
                        <button
                            type="button"
                            wire:click="sendGuestPin"
                            wire:loading.attr="disabled"
                            @disabled(! $event->isRegistrationOpen())
                            class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-brand-500 disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
                        >
                            <span wire:loading.remove wire:target="sendGuestPin">Email me a code &amp; continue</span>
                            <span wire:loading wire:target="sendGuestPin" class="inline-flex items-center justify-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                Sending…
                            </span>
                        </button>
                        @if (! $event->isRegistrationOpen())
                            <p class="text-xs text-slate-500">Registrations are closed or full.</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
