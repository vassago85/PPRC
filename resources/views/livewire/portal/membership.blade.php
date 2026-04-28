<div class="max-w-4xl mx-auto px-4 py-8 space-y-8">
    <header>
        <h1 class="text-2xl font-semibold text-slate-900">My Membership</h1>
        <p class="text-sm text-slate-600">Pretoria Precision Rifle Club</p>
    </header>

    @if (session('flash'))
        <div class="rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
            {{ session('flash') }}
        </div>
    @endif

    @if (! $this->member)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-6">
            <h2 class="font-medium text-amber-900">Profile incomplete</h2>
            <p class="text-sm text-amber-800 mt-1">
                You don't have a member profile yet. Contact the club to be added, or complete the registration form.
            </p>
        </div>
    @else
        <section class="rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-medium text-slate-900">Current status</h2>

            @if ($this->current)
                @php($m = $this->current)
                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-slate-500">Membership type</dt>
                        <dd class="font-medium text-slate-900">{{ $m->membership_type_name_snapshot }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Status</dt>
                        <dd><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-800">{{ $m->status->label() }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Period</dt>
                        <dd class="text-slate-900">{{ $m->period_start->format('d M Y') }} – {{ $m->period_end?->format('d M Y') ?? 'Life' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Price</dt>
                        <dd class="text-slate-900">R {{ number_format($m->price_cents_snapshot / 100, 2) }}</dd>
                    </div>
                </dl>

                @if ($m->status === App\Enums\MembershipStatus::Active && $m->certificate_token)
                    <div class="mt-6 rounded-lg border border-brand-200/80 bg-brand-50/60 p-4 text-sm text-slate-800">
                        <p class="font-medium text-slate-900">Membership certificate</p>
                        <p class="mt-1 text-slate-600">Print or save a PDF for range or employer checks.</p>
                        <a
                            href="{{ route('membership.certificate.show', ['token' => $m->certificate_token]) }}"
                            target="_blank"
                            rel="noopener"
                            class="mt-3 inline-flex items-center rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-500"
                        >Open certificate</a>
                    </div>
                @endif

                @if ($m->status === App\Enums\MembershipStatus::PendingPayment)
                    @php($pending = $m->payments->firstWhere('status', App\Enums\PaymentStatus::Pending))
                    @if (! $pending)
                        <div class="mt-6">
                            <button
                                type="button"
                                wire:click="startEftPayment({{ $m->id }})"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800 disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="startEftPayment({{ $m->id }})">Generate EFT reference</span>
                                <span wire:loading wire:target="startEftPayment({{ $m->id }})" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                            </button>
                        </div>
                    @else
                        <div class="mt-6 rounded-md bg-slate-50 border border-slate-200 p-4 text-sm">
                            <p class="font-medium text-slate-900">Bank transfer reference:
                                <span class="font-mono">{{ $pending->reference }}</span></p>
                            <p class="text-slate-600 mt-1">Pay R {{ number_format($pending->amount_cents / 100, 2) }} using this reference,
                                then upload proof below.</p>

                            <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-3">
                                <input type="file" wire:model="proofUpload" class="text-sm" />
                                <button
                                    type="button"
                                    wire:click="uploadProof({{ $pending->id }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-1.5 text-sm text-white hover:bg-emerald-500 disabled:opacity-60"
                                >
                                    <span wire:loading.remove wire:target="uploadProof({{ $pending->id }})">Upload proof</span>
                                    <span wire:loading wire:target="uploadProof({{ $pending->id }})" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                </button>
                            </div>
                            @error('proofUpload') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
                        </div>
                    @endif
                @endif
            @else
                <p class="mt-4 text-sm text-slate-600">You have no current membership.</p>
            @endif
        </section>

        @if ($this->clubBadges->isNotEmpty())
            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-medium text-slate-900">Club badges</h2>
                <p class="mt-1 text-sm text-slate-600">Recognition from the committee for contributions and roles.</p>
                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach ($this->clubBadges as $badge)
                        <li class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-800">
                            {{ $badge->name }}
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-medium text-slate-900">Renew / upgrade</h2>
            <div class="mt-4 flex flex-col sm:flex-row sm:items-end gap-3">
                <div class="flex-1">
                    <label class="block text-sm text-slate-700">Membership type</label>
                    <select wire:model="renewIntoTypeId" class="mt-1 block w-full rounded-md border border-slate-300 text-sm py-2 px-3">
                        <option value="">Choose…</option>
                        @foreach ($this->types as $t)
                            <option value="{{ $t->id }}">
                                {{ $t->name }} — R {{ number_format($t->price_cents / 100, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button
                    type="button"
                    wire:click="renew"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800 disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="renew">Request membership</span>
                    <span wire:loading wire:target="renew" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                </button>
            </div>
            @error('renewIntoTypeId') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
        </section>

        @if ($this->subMembers->count())
            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-medium text-slate-900">Linked sub-members</h2>
                <p class="text-sm text-slate-600 mt-1">
                    Juniors attached to your membership. Free while your membership is active — they auto-renew when you renew.
                </p>
                <ul class="mt-4 divide-y divide-slate-100 text-sm">
                    @foreach ($this->subMembers as $sub)
                        @php($sm = $sub->memberships->first())
                        <li class="py-3 flex items-center justify-between">
                            <div>
                                <p class="font-medium text-slate-900">{{ $sub->fullName() }}</p>
                                <p class="text-slate-500 text-xs">
                                    @if ($sub->date_of_birth) Born {{ $sub->date_of_birth->format('d M Y') }} @endif
                                    @if ($sm) · {{ $sm->membership_type_name_snapshot }} — {{ $sm->status->label() }} @endif
                                </p>
                            </div>
                            @if ($sm)
                                <span class="text-xs text-slate-500">
                                    Expires {{ $sm->period_end->format('d M Y') }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($this->history->count())
            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-medium text-slate-900">History</h2>
                <table class="mt-4 w-full text-sm">
                    <thead class="text-left text-slate-500">
                        <tr>
                            <th class="font-normal pb-2">Type</th>
                            <th class="font-normal pb-2">Period</th>
                            <th class="font-normal pb-2">Status</th>
                            <th class="font-normal pb-2 text-right">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->history as $h)
                            <tr class="border-t border-slate-100">
                                <td class="py-2 text-slate-900">{{ $h->membership_type_name_snapshot }}</td>
                                <td class="py-2 text-slate-700">{{ $h->period_start->format('M Y') }} – {{ $h->period_end->format('M Y') }}</td>
                                <td class="py-2 text-slate-700">{{ $h->status->label() }}</td>
                                <td class="py-2 text-right text-slate-900">R {{ number_format($h->price_cents_snapshot / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
    @endif
</div>
