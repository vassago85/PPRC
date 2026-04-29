<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-white">Documents</h1>
        <p class="mt-1 text-sm text-slate-400">Certificates, letters, and endorsements.</p>
    </div>

    @if (session('flash'))
        <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            {{ session('flash') }}
        </div>
    @endif
    @if (session('flash_error'))
        <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            {{ session('flash_error') }}
        </div>
    @endif

    {{-- Membership certificate --}}
    <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Membership Certificate</h2>
                <p class="mt-1 text-sm text-slate-400">Proof of your current active membership.</p>
            </div>
            <div class="shrink-0">
                <svg class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
            </div>
        </div>
        @php
            $membership = $this->member?->currentMembership();
            $canCert = $membership
                && $membership->status === App\Enums\MembershipStatus::Active
                && $membership->certificate_token;
        @endphp
        @if ($canCert)
            <a href="{{ route('membership.certificate.show', ['token' => $membership->certificate_token]) }}"
               target="_blank" rel="noopener"
               class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-white/15">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                View certificate
            </a>
        @else
            <p class="mt-4 text-sm text-slate-500">Available once your membership is active.</p>
        @endif
    </section>

    {{-- Participation letter --}}
    <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Participation Letter</h2>
                <p class="mt-1 text-sm text-slate-400">Official letter listing all club events you have participated in.</p>
            </div>
            <div class="shrink-0">
                <svg class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" /></svg>
            </div>
        </div>
        @if ($this->member)
            <a href="{{ route('portal.documents.participation') }}"
               target="_blank" rel="noopener"
               class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-white/15">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                View letter
            </a>
        @else
            <p class="mt-4 text-sm text-slate-500">Available once your member profile is set up.</p>
        @endif
    </section>

    {{-- Firearm endorsements --}}
    <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Firearm Licence Endorsements</h2>
                <p class="mt-1 text-sm text-slate-400">Request an endorsement letter from the club to support a new firearm licence application. Must be approved by the committee.</p>
            </div>
            <div class="shrink-0">
                <svg class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
            </div>
        </div>

        {{-- Existing endorsements --}}
        @if ($this->endorsements->count())
            <ul class="mt-4 space-y-2">
                @foreach ($this->endorsements as $e)
                    <li class="flex items-center justify-between rounded-lg border border-white/5 bg-white/[0.02] px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-white">{{ $e->firearm_type }} — {{ $e->reason }}</p>
                            <p class="text-xs text-slate-500">Requested {{ $e->created_at->format('d M Y') }}</p>
                        </div>
                        @php($eColor = match($e->status->color()) {
                            'success' => 'bg-emerald-500/20 text-emerald-400 ring-emerald-500/30',
                            'warning' => 'bg-amber-500/20 text-amber-400 ring-amber-500/30',
                            'danger'  => 'bg-red-500/20 text-red-400 ring-red-500/30',
                            default   => 'bg-slate-500/20 text-slate-400 ring-slate-500/30',
                        })
                        <div class="flex items-center gap-3 shrink-0 ml-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $eColor }}">
                                {{ $e->status->label() }}
                            </span>
                            @if ($e->status === App\Enums\EndorsementStatus::Approved && $e->token)
                                <a href="{{ route('portal.documents.endorsement', $e->token) }}"
                                   target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-white/15">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                    View letter
                                </a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- New request form --}}
        @if ($this->hasActiveMembership && ! $this->hasPendingEndorsement)
            <div class="mt-6 rounded-xl border border-white/10 bg-white/[0.02] p-5">
                <h3 class="text-sm font-semibold text-white">New endorsement request</h3>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm text-slate-400">Purpose / reason</label>
                        <input type="text" wire:model="reason" placeholder="e.g. Dedicated sport shooting"
                            class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                        @error('reason') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400">Firearm type</label>
                        <input type="text" wire:model="firearmType" placeholder="e.g. Bolt-action rifle, Semi-auto pistol"
                            class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                        @error('firearmType') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400">Firearm details <span class="text-slate-600">(optional — make, model, calibre)</span></label>
                        <input type="text" wire:model="firearmDetails" placeholder="e.g. Tikka T3x TAC A1 .308 Win"
                            class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                        @error('firearmDetails') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400">Motivation</label>
                        <textarea wire:model="motivation" rows="3" placeholder="Briefly explain why you need this firearm for sport shooting at the club..."
                            class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20"></textarea>
                        @error('motivation') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <button type="button" wire:click="requestEndorsement" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:opacity-50">
                        <span wire:loading.remove wire:target="requestEndorsement">Submit request</span>
                        <span wire:loading wire:target="requestEndorsement" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>
                    </button>
                </div>
            </div>
        @elseif ($this->hasPendingEndorsement)
            <p class="mt-4 text-sm text-amber-400">You have a pending endorsement request under review.</p>
        @elseif (! $this->hasActiveMembership)
            <p class="mt-4 text-sm text-slate-500">You need an active membership to request endorsements.</p>
        @endif
    </section>
</div>
