@php
    /** @var \App\Models\Member $record */
    $record = $this->record;
    $standing = $record->standing();
    $blocking = $this->blockingReason();
    $checklist = $this->onboardingChecklist();
    $timeline = $this->timeline();
    $payments = $this->payments();
    $matches = $this->matchEntries();
@endphp

<x-filament-panels::page>
    <x-ui.page>
        {{-- Header row: status pill, member number, joined + verified line --}}
        <x-ui.panel>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.pill
                            :variant="match($standing->color()) { 'success'=>'ok','warning'=>'warn','danger'=>'crit','info'=>'info', default=>'muted' }"
                        >
                            {{ $standing->label() }}
                        </x-ui.pill>

                        @if ($record->membership_number)
                            <span class="ui-cell--mono text-[--ui-ink-2]">{{ $record->formattedMembershipNumber() }}</span>
                        @endif

                        @if ($record->isJunior())
                            <span class="ui-chip">Junior</span>
                        @endif

                        @if ($record->user?->isCommittee())
                            <span class="ui-chip">Committee</span>
                        @endif
                    </div>

                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-[color:var(--ui-ink-2)]">
                        @if ($record->join_date)
                            <span>Joined {{ $record->join_date->format('d M Y') }}</span>
                        @endif
                        @if ($record->expiry_date)
                            <span>Expires {{ $record->expiry_date->format('d M Y') }}</span>
                        @endif
                        @if ($record->user?->email && ! $record->hasPlaceholderEmail())
                            <span>{{ $record->user->email }}</span>
                        @endif
                        @if ($record->phone_number)
                            <span>{{ $record->phone_country_code }} {{ $record->phone_number }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui.panel>

        {{-- Blocking callout when the record cannot progress --}}
        @if ($blocking)
            <x-ui.panel>
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-[color:var(--ui-warn)] mt-0.5" />
                    <div>
                        <div class="text-sm font-semibold text-[color:var(--ui-ink)]">Blocking progress</div>
                        <p class="mt-1 text-sm text-[color:var(--ui-ink-2)]">{{ $blocking }}</p>
                    </div>
                </div>
            </x-ui.panel>
        @endif

        {{-- Segments (tabs) --}}
        <x-ui.segments
            name="tab"
            :active="$tab"
            :segments="[
                ['key' => 'overview',   'label' => 'Overview'],
                ['key' => 'memberships','label' => 'Memberships'],
                ['key' => 'payments',   'label' => 'Payments', 'count' => count($payments)],
                ['key' => 'matches',    'label' => 'Matches', 'count' => count($matches)],
                ['key' => 'notes',      'label' => 'Notes'],
            ]"
        />

        @if ($tab === 'overview')
            <div class="grid gap-4 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <x-ui.panel title="Timeline">
                        @if (empty($timeline))
                            <x-ui.empty
                                icon="heroicon-o-clock"
                                title="Nothing has happened yet"
                                description="Once this member confirms their email, chooses a membership or makes a payment, those events will show up here."
                            />
                        @else
                            <ol class="space-y-3">
                                @foreach ($timeline as $item)
                                    <li class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex h-7 w-7 flex-none items-center justify-center rounded-full bg-[color:var(--ui-surface-2)] text-[color:var(--ui-ink-2)]">
                                            <x-dynamic-component :component="$item['icon']" class="w-4 h-4" />
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-sm text-[color:var(--ui-ink)]">{{ $item['title'] }}</div>
                                            <div class="text-xs text-[color:var(--ui-ink-3)] ui-cell--mono">{{ $item['detail'] }}</div>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </x-ui.panel>
                </div>

                <div class="space-y-4">
                    <x-ui.panel title="At a glance">
                        <dl class="grid grid-cols-1 gap-3 text-sm">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-[color:var(--ui-ink-3)]">Member number</dt>
                                <dd class="ui-cell--mono">{{ $record->formattedMembershipNumber() ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-[color:var(--ui-ink-3)]">Membership</dt>
                                <dd>{{ $record->currentMembership()?->membershipType?->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-[color:var(--ui-ink-3)]">Discipline(s)</dt>
                                <dd>{{ is_array($record->shooting_disciplines) ? implode(', ', $record->shooting_disciplines) : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-[color:var(--ui-ink-3)]">SAPRF #</dt>
                                <dd class="ui-cell--mono">{{ $record->saprf_membership_number ?? '—' }}</dd>
                            </div>
                        </dl>
                    </x-ui.panel>

                    <x-ui.panel title="Onboarding checklist">
                        <ul class="space-y-2 text-sm">
                            @foreach ($checklist as $item)
                                <li class="flex items-center gap-2">
                                    @if ($item['done'])
                                        <x-heroicon-o-check-circle class="w-4 h-4 text-[color:var(--ui-good)]" />
                                    @else
                                        <x-heroicon-o-minus-circle class="w-4 h-4 text-[color:var(--ui-ink-3)]" />
                                    @endif
                                    <span @class(['text-[color:var(--ui-ink-3)] line-through' => $item['done']])>{{ $item['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </x-ui.panel>
                </div>
            </div>
        @elseif ($tab === 'memberships')
            <x-ui.panel title="Memberships" flush>
                @if ($record->memberships->isEmpty())
                    <x-ui.empty
                        icon="heroicon-o-identification"
                        title="No memberships yet"
                        description="This member hasn't started a membership application yet."
                    />
                @else
                    <x-ui.table :columns="[
                        ['label' => 'Type'],
                        ['label' => 'Status'],
                        ['label' => 'Period'],
                        ['label' => 'Created', 'align' => 'right'],
                    ]">
                        @foreach ($record->memberships as $membership)
                            <tr>
                                <td>{{ $membership->membershipType?->name ?? '—' }}</td>
                                <td>
                                    <x-ui.pill variant="{{ $membership->status?->value === 'active' ? 'ok' : 'warn' }}">
                                        {{ ucfirst(str_replace('_', ' ', (string) $membership->status?->value)) }}
                                    </x-ui.pill>
                                </td>
                                <td class="ui-cell--mono text-sm">
                                    {{ $membership->period_start?->format('d M Y') ?? '—' }}
                                    →
                                    {{ $membership->period_end?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="ui-cell--right ui-cell--mono">{{ $membership->created_at?->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif
            </x-ui.panel>
        @elseif ($tab === 'payments')
            <x-ui.panel title="Payments" flush>
                @if (empty($payments))
                    <x-ui.empty
                        icon="heroicon-o-banknotes"
                        title="No payments recorded"
                        description="Any membership or match payments this member makes will show up here."
                    />
                @else
                    <x-ui.table :columns="[
                        ['label' => 'Reference'],
                        ['label' => 'Amount', 'align' => 'right'],
                        ['label' => 'Status'],
                        ['label' => 'Submitted', 'align' => 'right'],
                        ['label' => 'Confirmed', 'align' => 'right'],
                    ]">
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="ui-cell--mono">{{ $payment->reference }}</td>
                                <td><x-ui.money :cents="$payment->amount_cents" /></td>
                                <td>
                                    <x-ui.pill variant="{{ match($payment->status?->value) { 'confirmed' => 'ok', 'submitted' => 'warn', 'failed', 'cancelled' => 'crit', default => 'muted' } }}">
                                        {{ $payment->status?->label() ?? '—' }}
                                    </x-ui.pill>
                                </td>
                                <td class="ui-cell--right ui-cell--mono">{{ $payment->submitted_at?->format('d M Y') ?? '—' }}</td>
                                <td class="ui-cell--right ui-cell--mono">{{ $payment->confirmed_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif
            </x-ui.panel>
        @elseif ($tab === 'matches')
            <x-ui.panel title="Match entries" flush>
                @if (empty($matches))
                    <x-ui.empty
                        icon="heroicon-o-flag"
                        title="Not entered in any matches"
                        description="Match entries this member makes through the portal or admin will show up here."
                    />
                @else
                    <x-ui.table :columns="[
                        ['label' => 'Match'],
                        ['label' => 'Date'],
                        ['label' => 'Division'],
                        ['label' => 'Status'],
                    ]">
                        @foreach ($matches as $entry)
                            <tr>
                                <td>{{ $entry->event?->title ?? '—' }}</td>
                                <td class="ui-cell--mono">{{ $entry->event?->start_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $entry->division ?? '—' }}</td>
                                <td>{{ $entry->status?->value ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif
            </x-ui.panel>
        @elseif ($tab === 'notes')
            <x-ui.panel title="Committee notes">
                @if (filled($record->notes))
                    <p class="whitespace-pre-wrap text-sm">{{ $record->notes }}</p>
                @else
                    <x-ui.empty
                        icon="heroicon-o-pencil-square"
                        title="No notes yet"
                        description="Notes are visible to the committee only."
                    >
                        <x-slot:action>
                            <x-ui.btn :href="\App\Filament\Admin\Resources\Members\MemberResource::getUrl('edit', ['record' => $record])" variant="primary">
                                Add a note
                            </x-ui.btn>
                        </x-slot:action>
                    </x-ui.empty>
                @endif
            </x-ui.panel>
        @endif
    </x-ui.page>
</x-filament-panels::page>
