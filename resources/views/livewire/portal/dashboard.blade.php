<div class="space-y-8">

    {{-- Flash messages --}}
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

    {{-- Hero greeting --}}
    <div>
        <h1 class="text-3xl font-bold tracking-tight">
            {{ $this->member?->first_name ?? auth()->user()->name }}
        </h1>
        <p class="mt-1 text-slate-400">Welcome back to PPRC.</p>
    </div>

    {{-- Membership card --}}
    <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 sm:p-8">
        @if ($this->membership)
            @php $m = $this->membership; @endphp
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Membership</p>
                    <p class="mt-1 text-xl font-semibold">{{ $m->membership_type_name_snapshot }}</p>
                    @if ($this->member?->membership_number)
                        <div class="mt-3 inline-flex items-baseline gap-3 rounded-lg border border-white/10 bg-white/5 px-4 py-2">
                            <span class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Member #</span>
                            <span class="font-mono text-2xl font-bold tracking-wider text-white sm:text-3xl">{{ $this->member->membership_number }}</span>
                        </div>
                    @endif
                </div>
                <div class="flex items-center gap-4">
                    @php
                        $statusColor = match($m->status->color()) {
                            'success' => 'bg-emerald-500/20 text-emerald-400 ring-emerald-500/30',
                            'warning' => 'bg-amber-500/20 text-amber-400 ring-amber-500/30',
                            'info'    => 'bg-sky-500/20 text-sky-400 ring-sky-500/30',
                            'danger'  => 'bg-red-500/20 text-red-400 ring-red-500/30',
                            default   => 'bg-slate-500/20 text-slate-400 ring-slate-500/30',
                        };
                    @endphp
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusColor }}">
                        {{ $m->status->label() }}
                    </span>
                    @if ($m->period_end)
                        <span class="text-sm text-slate-400">Expires {{ $m->period_end->format('d M Y') }}</span>
                    @endif
                </div>
            </div>

            {{-- Certificate link --}}
            @if ($m->status === App\Enums\MembershipStatus::Active && $m->certificate_token)
                <div class="mt-6 flex">
                    <a href="{{ route('membership.certificate.show', ['token' => $m->certificate_token]) }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/15">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        Download certificate
                    </a>
                </div>
            @endif

            {{-- Pending payment: show banking details + upload --}}
            @if ($m->status === App\Enums\MembershipStatus::PendingPayment && $this->pendingPayment)
                @php $pay = $this->pendingPayment; @endphp
                <div class="mt-6 rounded-xl border border-amber-500/20 bg-amber-500/5 p-5">
                    <p class="text-sm font-semibold text-amber-300">Payment required</p>
                    <p class="mt-2 text-sm text-slate-300">
                        Transfer <span class="font-semibold text-white">R {{ number_format($pay->amount_cents / 100, 2) }}</span>
                        using reference <span class="font-mono font-semibold text-white">{{ $pay->reference }}</span>,
                        then upload your proof of payment.
                    </p>
                    <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-3">
                        <input type="file" wire:model="proofUpload"
                            class="text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-white/15" />
                        <button type="button" wire:click="uploadProof({{ $pay->id }})" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-400 disabled:opacity-50">
                            <span wire:loading.remove wire:target="uploadProof({{ $pay->id }})">Upload proof</span>
                            <span wire:loading wire:target="uploadProof({{ $pay->id }})" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>
                        </button>
                    </div>
                    @error('proofUpload') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            @endif

            {{-- Renewal CTA --}}
            @if ($this->needsRenewal && $m->status !== App\Enums\MembershipStatus::PendingPayment && $m->status !== App\Enums\MembershipStatus::PendingApproval)
                <div class="mt-6 rounded-xl border border-white/10 bg-white/[0.03] p-5">
                    <p class="text-sm font-semibold">
                        @if ($m->status === App\Enums\MembershipStatus::Expired)
                            Your membership has expired.
                        @else
                            Your membership expires soon.
                        @endif
                    </p>
                    <div class="mt-3 flex flex-col sm:flex-row sm:items-end gap-3">
                        <select wire:model="renewIntoTypeId"
                            class="flex-1 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20">
                            <option value="">Choose type...</option>
                            @foreach ($this->types as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} — R {{ number_format($t->price_cents / 100, 2) }}</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="renew" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:opacity-50">
                            <span wire:loading.remove wire:target="renew">Renew now</span>
                            <span wire:loading wire:target="renew" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>
                        </button>
                    </div>
                    @error('renewIntoTypeId') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            @endif

        @else
            {{-- No membership at all --}}
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Membership</p>
            <p class="mt-2 text-sm text-slate-400">You don't have a membership yet.</p>
            <div class="mt-4 flex flex-col sm:flex-row sm:items-end gap-3">
                <select wire:model="renewIntoTypeId"
                    class="flex-1 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20">
                    <option value="">Choose a membership...</option>
                    @foreach ($this->types as $t)
                        <option value="{{ $t->id }}">{{ $t->name }} — R {{ number_format($t->price_cents / 100, 2) }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="renew" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:opacity-50">
                    <span wire:loading.remove wire:target="renew">Join now</span>
                    <span wire:loading wire:target="renew" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>
                </button>
            </div>
            @error('renewIntoTypeId') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
        @endif
    </section>

    {{-- Two-column grid: matches + results --}}
    <div class="grid gap-8 lg:grid-cols-2">

        {{-- Upcoming matches --}}
        <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Upcoming matches</h2>
                <a href="{{ route('matches') }}" class="text-xs text-slate-500 transition hover:text-white">View all</a>
            </div>

            @if ($this->upcomingMatches->isEmpty())
                <p class="mt-6 text-sm text-slate-500">No matches scheduled.</p>
            @else
                <ul class="mt-4 space-y-1">
                    @foreach ($this->upcomingMatches as $event)
                        <li>
                            <a href="{{ route('matches.show', $event) }}" class="group flex items-center gap-4 rounded-lg px-3 py-3 -mx-3 transition hover:bg-white/5">
                                <div class="shrink-0 w-11 text-center">
                                    <span class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $event->start_date->format('M') }}</span>
                                    <span class="block text-lg font-bold leading-tight text-white">{{ $event->start_date->format('d') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-white group-hover:text-slate-200">{{ $event->title }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $event->matchFormat?->short_name }}@if ($event->location_name) · {{ $event->location_name }}@endif
                                    </p>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Recent results --}}
        <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">My results</h2>
                <a href="{{ route('portal.results') }}" class="text-xs text-slate-500 transition hover:text-white">View all</a>
            </div>

            @if ($this->recentResults->isEmpty())
                <p class="mt-6 text-sm text-slate-500">No results yet.</p>
            @else
                <ul class="mt-4 space-y-1">
                    @foreach ($this->recentResults as $result)
                        <li class="flex items-center justify-between rounded-lg px-3 py-3 -mx-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-white">{{ $result->event?->title ?? '—' }}</p>
                                <p class="text-xs text-slate-500">{{ $result->event?->start_date?->format('d M Y') ?? '' }}</p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0 ml-4">
                                @if ($result->rank && $result->rank <= 3)
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-500/20 text-xs font-bold text-amber-400">{{ $result->rank }}</span>
                                @elseif ($result->rank)
                                    <span class="text-sm text-slate-400">#{{ $result->rank }}</span>
                                @endif
                                <span class="text-sm font-mono text-slate-300">{{ $result->displayScore() }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- Quick actions row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <a href="{{ route('portal.registrations') }}" class="group flex flex-col items-center gap-2 rounded-xl border border-white/10 bg-white/[0.03] p-5 text-center transition hover:border-white/20 hover:bg-white/[0.06]">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-slate-400 transition group-hover:text-white">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
            </div>
            <span class="text-xs font-medium text-slate-400 group-hover:text-white">Registrations</span>
        </a>
        <a href="{{ route('portal.results') }}" class="group flex flex-col items-center gap-2 rounded-xl border border-white/10 bg-white/[0.03] p-5 text-center transition hover:border-white/20 hover:bg-white/[0.06]">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-slate-400 transition group-hover:text-white">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .982-3.172M8.25 8.25a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0Z" /></svg>
            </div>
            <span class="text-xs font-medium text-slate-400 group-hover:text-white">All results</span>
        </a>
        <a href="{{ route('portal.documents') }}" class="group flex flex-col items-center gap-2 rounded-xl border border-white/10 bg-white/[0.03] p-5 text-center transition hover:border-white/20 hover:bg-white/[0.06]">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-slate-400 transition group-hover:text-white">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
            </div>
            <span class="text-xs font-medium text-slate-400 group-hover:text-white">Documents</span>
        </a>
        <a href="{{ route('portal.profile.edit') }}" class="group flex flex-col items-center gap-2 rounded-xl border border-white/10 bg-white/[0.03] p-5 text-center transition hover:border-white/20 hover:bg-white/[0.06]">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-slate-400 transition group-hover:text-white">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
            </div>
            <span class="text-xs font-medium text-slate-400 group-hover:text-white">Profile</span>
        </a>
    </div>
</div>
