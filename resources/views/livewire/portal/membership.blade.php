<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-white">My Membership</h1>
        <p class="mt-1 text-sm text-slate-400">Pretoria Precision Rifle Club</p>
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

    @if (! $this->member)
        <div class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-6">
            <h2 class="font-semibold text-amber-300">Profile incomplete</h2>
            <p class="mt-1 text-sm text-slate-300">
                You don't have a member profile yet. Contact the club to be added, or complete registration.
            </p>
        </div>
    @else
        {{-- Current membership --}}
        <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 sm:p-8">
            @if ($this->current)
                @php $m = $this->current; @endphp
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Current membership</p>
                        <p class="mt-1 text-xl font-bold text-white">{{ $m->membership_type_name_snapshot }}</p>
                        @if ($this->member->membership_number)
                            <p class="mt-1 text-xs text-slate-400">
                                Member #
                                <span class="font-mono font-semibold text-slate-200">{{ $this->member->membership_number }}</span>
                            </p>
                        @endif
                    </div>
                    @php
                        $statusColor = match($m->status->color()) {
                            'success' => 'bg-emerald-500/20 text-emerald-400 ring-emerald-500/30',
                            'warning' => 'bg-amber-500/20 text-amber-400 ring-amber-500/30',
                            'info'    => 'bg-sky-500/20 text-sky-400 ring-sky-500/30',
                            'danger'  => 'bg-red-500/20 text-red-400 ring-red-500/30',
                            default   => 'bg-slate-500/20 text-slate-400 ring-slate-500/30',
                        };
                    @endphp
                    <span class="inline-flex self-start items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusColor }}">
                        {{ $m->status->label() }}
                    </span>
                </div>

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
                    <div>
                        <dt class="text-slate-500">Period</dt>
                        <dd class="mt-1 font-medium text-white">{{ $m->period_start->format('d M Y') }} — {{ $m->period_end?->format('d M Y') ?? 'Life' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Price paid</dt>
                        <dd class="mt-1 font-medium text-white">
                            @if ($m->price_cents_snapshot !== null)
                                R {{ number_format($m->price_cents_snapshot / 100, 2) }}
                            @else
                                <span class="text-slate-500">— <span class="text-xs">(not on record)</span></span>
                            @endif
                        </dd>
                    </div>
                    @if ($m->period_end)
                        <div>
                            <dt class="text-slate-500">Expires</dt>
                            <dd class="mt-1 font-medium text-white">{{ $m->period_end->format('d M Y') }}</dd>
                        </div>
                    @endif
                </dl>

                {{-- Certificate --}}
                @if ($m->status === App\Enums\MembershipStatus::Active && $m->certificate_token)
                    <div class="mt-6">
                        <a href="{{ route('membership.certificate.show', ['token' => $m->certificate_token]) }}"
                           target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-white/15">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                            Download certificate
                        </a>
                    </div>
                @endif

                {{-- Pending payment --}}
                @if ($m->status === App\Enums\MembershipStatus::PendingPayment)
                    @php
                        $pending = $m->payments->firstWhere('status', App\Enums\PaymentStatus::Pending)
                            ?? $m->payments->firstWhere('status', App\Enums\PaymentStatus::Submitted);
                    @endphp
                    @if ($pending)
                        <div class="mt-6">
                            <x-portal.banking-details :payment="$pending" />
                        </div>
                    @else
                        <div class="mt-6">
                            <button type="button" wire:click="startEftPayment({{ $m->id }})" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:opacity-50">
                                <span wire:loading.remove wire:target="startEftPayment({{ $m->id }})">Generate EFT reference</span>
                                <span wire:loading wire:target="startEftPayment({{ $m->id }})" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>
                            </button>
                        </div>
                    @endif
                @endif

                {{-- Pending approval --}}
                @if ($m->status === App\Enums\MembershipStatus::PendingApproval)
                    <div class="mt-6 rounded-xl border border-sky-500/20 bg-sky-500/5 p-5">
                        <p class="text-sm font-semibold text-sky-300">Awaiting approval</p>
                        <p class="mt-2 text-sm text-slate-300">
                            Your proof of payment has been submitted. The committee will verify and activate your membership shortly.
                        </p>
                    </div>
                @endif
            @else
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Current membership</p>
                <p class="mt-2 text-sm text-slate-400">You have no current membership.</p>
            @endif
        </section>

        {{-- Club badges --}}
        @if ($this->clubBadges->isNotEmpty())
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Club badges</h2>
                <p class="mt-1 text-sm text-slate-400">Recognition from the committee.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($this->clubBadges as $badge)
                        <span class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-slate-300">
                            {{ $badge->name }}
                        </span>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Renew / upgrade --}}
        <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Renew / upgrade</h2>
            <div class="mt-4 flex flex-col sm:flex-row sm:items-end gap-3">
                <div class="flex-1">
                    <label class="block text-sm text-slate-400">Membership type</label>
                    <select wire:model="renewIntoTypeId"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20">
                        <option value="">Choose...</option>
                        @foreach ($this->types as $t)
                            <option value="{{ $t->id }}">{{ $t->name }} — R {{ number_format($t->price_cents / 100, 2) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" wire:click="renew" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:opacity-50">
                    <span wire:loading.remove wire:target="renew">Request membership</span>
                    <span wire:loading wire:target="renew" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>
                </button>
            </div>
            @error('renewIntoTypeId') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
        </section>

        {{-- Family (juniors + spouse) --}}
        @if ($this->canAddFamily || $this->family->count())
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Family</h2>
                        <p class="mt-1 text-sm text-slate-400">Add your kids or spouse so you can enter them in matches and pay their fees from your account.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($this->canAddJunior)
                            <button type="button" wire:click="openFamilyForm('junior')"
                                class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-white/10">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Add a junior
                            </button>
                        @endif
                        @if ($this->canAddSpouse)
                            <button type="button" wire:click="openFamilyForm('spouse')"
                                class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-white/10">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Add a spouse
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Inline add form --}}
                @if ($this->familyForm)
                    <div wire:key="family-form-{{ $this->familyForm }}" class="mt-5 rounded-xl border border-white/10 bg-slate-950/40 p-5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-white">
                                    {{ $this->familyForm === 'junior' ? 'Add a junior' : 'Add a spouse' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    @if ($this->familyForm === 'junior')
                                        Under 18, linked to your membership. Free while your membership is active.
                                    @else
                                        Membership fee applies. You'll see the EFT reference and proof upload here after adding.
                                    @endif
                                </p>
                            </div>
                            <button type="button" wire:click="cancelFamilyForm" class="text-xs text-slate-500 hover:text-slate-300">Cancel</button>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-xs font-medium uppercase tracking-wider text-slate-500">First name</label>
                                <input type="text" wire:model="familyFirstName" dusk="family-first-name"
                                    class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                                @error('familyFirstName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-medium uppercase tracking-wider text-slate-500">Last name</label>
                                <input type="text" wire:model="familyLastName" dusk="family-last-name"
                                    class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                                @error('familyLastName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-medium uppercase tracking-wider text-slate-500">
                                    Date of birth{{ $this->familyForm === 'junior' ? '' : ' (optional)' }}
                                </label>
                                <input type="date" wire:model="familyDob" dusk="family-dob"
                                    class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                                @error('familyDob') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-medium uppercase tracking-wider text-slate-500">Email (optional)</label>
                                <input type="email" wire:model="familyEmail" dusk="family-email"
                                    class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/80 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20"
                                    placeholder="Leave blank if you'll manage them" />
                                @error('familyEmail') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            Leave email blank if they don't need their own login — you'll manage them here.
                        </p>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <button type="button" wire:click="addFamily" wire:loading.attr="disabled" wire:target="addFamily" dusk="family-add-submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:opacity-50">
                                <span wire:loading.remove wire:target="addFamily">
                                    {{ $this->familyForm === 'junior' ? 'Add junior' : 'Add spouse' }}
                                </span>
                                <span wire:loading wire:target="addFamily" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Family cards --}}
                @if ($this->family->count())
                    <ul class="mt-5 space-y-3">
                        @foreach ($this->family as $sub)
                            @php
                                $sm = $sub->memberships->first();
                                $isSpouse = $sm && $sm->membership_type_slug_snapshot === 'spouse';
                                $pending = $sm?->payments?->firstWhere('status', App\Enums\PaymentStatus::Pending)
                                    ?? $sm?->payments?->firstWhere('status', App\Enums\PaymentStatus::Submitted);
                            @endphp
                            <li wire:key="family-{{ $sub->id }}" dusk="family-card-{{ $sub->id }}" class="rounded-xl border border-white/10 bg-white/[0.02] p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-white">{{ $sub->fullName() }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            @if ($sub->date_of_birth)
                                                Born {{ $sub->date_of_birth->format('d M Y') }}
                                                @if ($sub->ageOnDate(now()) !== null)
                                                    · Age {{ $sub->ageOnDate(now()) }}
                                                @endif
                                            @endif
                                            @if ($sm)
                                                · {{ $sm->membership_type_name_snapshot }} — {{ $sm->status->label() }}
                                            @endif
                                        </p>
                                        @if ($sm && ! $isSpouse && $sm->status === App\Enums\MembershipStatus::Active)
                                            <p class="mt-1 text-xs text-emerald-300">Free while your membership is active.</p>
                                        @endif
                                    </div>
                                    <a href="{{ url('/matches') }}" class="text-xs font-semibold text-brand-300 hover:text-brand-200">
                                        Enter them in a match →
                                    </a>
                                </div>

                                {{-- Spouse EFT card + proof upload --}}
                                @if ($isSpouse && $sm && $sm->status === App\Enums\MembershipStatus::PendingPayment)
                                    <div class="mt-4">
                                        @if ($pending)
                                            <x-portal.banking-details :payment="$pending" />
                                        @else
                                            <button type="button" wire:click="startEftPayment({{ $sm->id }})" wire:loading.attr="disabled"
                                                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:opacity-50">
                                                <span wire:loading.remove wire:target="startEftPayment({{ $sm->id }})">Generate EFT reference</span>
                                                <span wire:loading wire:target="startEftPayment({{ $sm->id }})" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>
                                            </button>
                                        @endif
                                    </div>
                                @elseif ($isSpouse && $sm && $sm->status === App\Enums\MembershipStatus::PendingApproval)
                                    <p class="mt-3 text-xs text-sky-300">Proof of payment uploaded — awaiting committee approval.</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-5 text-sm text-slate-400">
                        No family members linked yet. Add your kids or spouse to enter the whole household in matches from one account.
                    </p>
                @endif
            </section>
        @endif

        {{-- History --}}
        @if ($this->history->count())
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] overflow-hidden">
                <div class="px-6 pt-6 pb-2">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500">History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-white/10 text-left text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-6 py-3 font-medium">Type</th>
                                <th class="px-6 py-3 font-medium">Period</th>
                                <th class="px-6 py-3 font-medium">Status</th>
                                <th class="px-6 py-3 font-medium text-right">Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($this->history as $h)
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="px-6 py-3 text-white">{{ $h->membership_type_name_snapshot }}</td>
                                    <td class="px-6 py-3 text-slate-300">{{ $h->period_start->format('M Y') }} — {{ $h->period_end?->format('M Y') ?? 'Life' }}</td>
                                    <td class="px-6 py-3 text-slate-300">{{ $h->status->label() }}</td>
                                    <td class="px-6 py-3 text-right text-white">
                                        @if ($h->price_cents_snapshot !== null)
                                            R {{ number_format($h->price_cents_snapshot / 100, 2) }}
                                        @else
                                            <span class="text-slate-500">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    @endif
</div>
