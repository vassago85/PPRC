@php
    /** @var \App\Models\Member $record */
    $record = $this->record;
    $pill = $this->recordPill();
    $glance = $this->atAGlance();
    $blocking = $this->blockingReason();
    $checklist = $this->onboardingChecklist();
    $timeline = $this->timeline();
    $payments = $this->payments();
    $matches = $this->matchEntries();
    $editUrl = \App\Filament\Admin\Resources\Members\MemberResource::getUrl('edit', ['record' => $record]);

    $tabs = [
        ['key' => 'overview',    'label' => 'Overview'],
        ['key' => 'memberships', 'label' => 'Memberships', 'count' => $record->memberships->count()],
        ['key' => 'payments',    'label' => 'Payments',    'count' => count($payments)],
        ['key' => 'matches',     'label' => 'Matches',     'count' => count($matches)],
        ['key' => 'badges',      'label' => 'Badges & endorsements'],
        ['key' => 'notes',       'label' => 'Notes'],
    ];
@endphp

<x-filament-panels::page>
    <div class="pp-page">
        {{-- ============ RECORD HEAD ============ --}}
        <div class="pp-rec-head">
            <div class="pp-rec-top">
                <div class="pp-rec-id">
                    <h1>
                        {{ $record->fullName() }}
                        <span class="pp-pill pp-pill--{{ $pill['variant'] }}">
                            {{ $pill['label'] }}@if ($pill['day'] !== null) &middot; day {{ $pill['day'] }} @endif
                        </span>
                        @if ($record->isJunior())
                            <span class="pp-pill pp-pill--in pp-pill--plain">Junior</span>
                        @endif
                        @if ($record->user?->isCommittee())
                            <span class="pp-pill pp-pill--in pp-pill--plain">Committee</span>
                        @endif
                    </h1>
                    <div class="pp-rec-meta">
                        {!! $this->contactMetaLine() !!}
                    </div>
                </div>

                <div class="pp-rec-acts">
                    {{-- State-aware header actions defined in getHeaderActions(). --}}
                    @foreach ($this->getCachedHeaderActions() as $action)
                        {{ $action }}
                    @endforeach
                </div>
            </div>

            {{-- Tabs, integrated in the head like the prototype --}}
            <div class="pp-rec-tabs" role="tablist" aria-label="Record sections">
                @foreach ($tabs as $t)
                    <a href="?tab={{ $t['key'] }}"
                       wire:click.prevent="$set('tab', '{{ $t['key'] }}')"
                       role="tab"
                       aria-selected="{{ $tab === $t['key'] ? 'true' : 'false' }}"
                       class="pp-rtab{{ $tab === $t['key'] ? ' pp-rtab--on' : '' }}">
                        {{ $t['label'] }}
                        @if (! empty($t['count']))
                            <em>{{ $t['count'] }}</em>
                        @endif
                    </a>
                @endforeach

                <a href="{{ $editUrl }}" class="pp-rtab pp-rtab--edit">Edit details</a>
            </div>
        </div>

        {{-- ============ RECORD BODY ============ --}}
        <div class="pp-rec-body">
            @if ($tab === 'overview')
                <div class="pp-rec-grid">
                    {{-- LEFT: history timeline --}}
                    <div>
                        <div class="pp-lab">History</div>
                        @if (empty($timeline))
                            <div class="pp-card">
                                <div class="pp-card__body">
                                    Nothing has happened for this record yet — signup, email confirmation and payments will land here as they happen.
                                </div>
                            </div>
                        @else
                            <ul class="pp-tl">
                                {{-- Current wait as the first pending item when the record is mid-onboarding --}}
                                @if ($blocking && $pill['day'] !== null)
                                    <li class="pp-tl--pend">
                                        <div class="pp-tl__t">Waiting on {{ strtolower($pill['label']) }}</div>
                                        <div class="pp-tl__m">day {{ $pill['day'] }} in this stage</div>
                                    </li>
                                @endif
                                @foreach ($timeline as $item)
                                    @php
                                        $liClass = '';
                                        // Highlight the most-recent event.
                                        if ($loop->first && ! ($blocking && $pill['day'] !== null)) {
                                            $liClass = 'pp-tl--hi';
                                        }
                                    @endphp
                                    <li class="{{ $liClass }}">
                                        <div class="pp-tl__t">{{ $item['title'] }}</div>
                                        <div class="pp-tl__m">{{ $item['detail'] }}</div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- RIGHT: at-a-glance, checklist, blocking --}}
                    <div>
                        <div class="pp-card">
                            <h3>At a glance</h3>
                            <div class="pp-kvl">
                                @foreach ($glance as $row)
                                    <div>
                                        <span>{{ $row['label'] }}</span>
                                        <b class="{{ ! empty($row['mono']) ? 'pp-mono ' : '' }}{{ ! empty($row['dim']) ? 'pp-dim' : '' }}">{{ $row['value'] }}</b>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="pp-card">
                            <h3>Onboarding checklist</h3>
                            @foreach ($checklist as $item)
                                <div class="pp-check {{ $item['done'] ? 'pp-check--done' : '' }}">
                                    <div class="pp-check__bx">{!! $item['done'] ? '✓' : '' !!}</div>
                                    <div class="pp-check__ct">{{ $item['label'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        @if ($blocking)
                            <div class="pp-card pp-card--crit">
                                <h3>Blocking</h3>
                                <div class="pp-card__body">{{ $blocking }}</div>
                            </div>
                        @endif
                    </div>
                </div>

            @elseif ($tab === 'memberships')
                @if ($record->memberships->isEmpty())
                    <div class="pp-card">
                        <div class="pp-card__body">No memberships on file yet.</div>
                    </div>
                @else
                    <div class="pp-tw">
                        <table class="pp-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Period</th>
                                    <th style="text-align:right">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($record->memberships as $membership)
                                    <tr>
                                        <td>{{ $membership->membershipType?->name ?? '—' }}</td>
                                        <td>
                                            <span class="pp-pill {{ $membership->status?->value === 'active' ? 'pp-pill--ok' : 'pp-pill--wa' }}">
                                                {{ ucfirst(str_replace('_', ' ', (string) $membership->status?->value)) }}
                                            </span>
                                        </td>
                                        <td class="pp-td--mono">
                                            {{ $membership->period_start?->format('d M Y') ?? '—' }}
                                            → {{ $membership->period_end?->format('d M Y') ?? '—' }}
                                        </td>
                                        <td class="pp-td--right pp-td--mono">{{ $membership->created_at?->format('d M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            @elseif ($tab === 'payments')
                @if (empty($payments))
                    <div class="pp-card">
                        <div class="pp-card__body">No payments on file for this member.</div>
                    </div>
                @else
                    <div class="pp-tw">
                        <table class="pp-table">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th style="text-align:right">Amount</th>
                                    <th>Status</th>
                                    <th style="text-align:right">Submitted</th>
                                    <th style="text-align:right">Confirmed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    @php
                                        $variant = match ($payment->status?->value) {
                                            'confirmed' => 'pp-pill--ok',
                                            'submitted' => 'pp-pill--wa',
                                            'failed', 'cancelled' => 'pp-pill--cr',
                                            default => 'pp-pill--mu',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="pp-td--mono">{{ $payment->reference }}</td>
                                        <td class="pp-td--right pp-td--mono">R {{ number_format(($payment->amount_cents ?? 0) / 100, 2) }}</td>
                                        <td>
                                            <span class="pp-pill {{ $variant }}">{{ $payment->status?->label() ?? '—' }}</span>
                                        </td>
                                        <td class="pp-td--right pp-td--mono">{{ $payment->submitted_at?->format('d M Y') ?? '—' }}</td>
                                        <td class="pp-td--right pp-td--mono">{{ $payment->confirmed_at?->format('d M Y') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            @elseif ($tab === 'matches')
                @if (empty($matches))
                    <div class="pp-card">
                        <div class="pp-card__body">This member hasn't entered a match yet.</div>
                    </div>
                @else
                    <div class="pp-tw">
                        <table class="pp-table">
                            <thead>
                                <tr>
                                    <th>Match</th>
                                    <th>Date</th>
                                    <th>Division</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($matches as $entry)
                                    <tr>
                                        <td>{{ $entry->event?->title ?? '—' }}</td>
                                        <td class="pp-td--mono">{{ $entry->event?->start_date?->format('d M Y') ?? '—' }}</td>
                                        <td>{{ $entry->division ?? '—' }}</td>
                                        <td class="pp-td--dim">{{ $entry->status?->value ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            @elseif ($tab === 'badges')
                @if ($record->clubBadges->isEmpty())
                    <div class="pp-card">
                        <div class="pp-card__body">No badges awarded yet.</div>
                    </div>
                @else
                    <div class="pp-tw">
                        <table class="pp-table">
                            <thead>
                                <tr>
                                    <th>Badge</th>
                                    <th>Awarded</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($record->clubBadges as $badge)
                                    <tr>
                                        <td>{{ $badge->name }}</td>
                                        <td class="pp-td--mono">
                                            @php
                                                $awardedAt = $badge->pivot?->awarded_at;
                                                $formatted = null;
                                                if ($awardedAt) {
                                                    $formatted = \Illuminate\Support\Carbon::parse($awardedAt)->format('d M Y');
                                                }
                                            @endphp
                                            {{ $formatted ?? '—' }}
                                        </td>
                                        <td class="pp-td--dim">{{ $badge->pivot?->notes ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            @elseif ($tab === 'notes')
                <div class="pp-card">
                    <h3>Committee notes</h3>
                    @if (filled($record->notes))
                        <div class="pp-card__body" style="white-space:pre-wrap">{{ $record->notes }}</div>
                    @else
                        <div class="pp-card__body">
                            No notes yet. Notes are visible to the committee only —
                            <a href="{{ $editUrl }}" style="color:var(--ui-accent)">add one on the edit page</a>.
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
