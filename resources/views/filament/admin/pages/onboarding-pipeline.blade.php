@php
    $counts = $this->stageCounts();
    $fineprint = $this->stageFineprint();
    $rows = $this->rows();
    $totalPending = array_sum(array_diff_key($counts, ['abandoned' => null]));
    $stuckIds = $this->stuckIdsInCurrentStage();

    $stages = [
        ['key' => 'email_unconfirmed', 'index' => 1, 'label' => 'Email unconfirmed'],
        ['key' => 'choosing_plan',     'index' => 2, 'label' => 'Choosing plan'],
        ['key' => 'awaiting_payment',  'index' => 3, 'label' => 'Awaiting payment'],
        ['key' => 'ready_to_activate', 'index' => 4, 'label' => 'Ready to activate'],
        ['key' => 'abandoned',         'index' => 5, 'label' => 'Abandoned'],
    ];
@endphp

<x-filament-panels::page>
    <div class="pp-page">
        <div class="pp-phead">
            <div>
                <h1>Onboarding</h1>
                <div class="pp-sub">{!! $this->pipelineSubtitle() !!}</div>
            </div>
        </div>

        {{-- Pipe --}}
        <div class="pp-pipe" role="tablist" aria-label="Onboarding stages">
            @foreach ($stages as $stage)
                @php
                    $count = $counts[$stage['key']] ?? 0;
                    $active = $this->stage === $stage['key'];
                    $abandoned = $stage['key'] === 'abandoned';
                @endphp
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $active ? 'true' : 'false' }}"
                    wire:click="setStage('{{ $stage['key'] }}')"
                    class="pp-stage{{ $active ? ' pp-stage--on' : '' }}{{ $abandoned ? ' pp-stage--abandoned' : '' }}"
                >
                    <span class="pp-stage__lab">{{ $stage['index'] }} &middot; {{ $stage['label'] }}</span>
                    <span class="pp-stage__n">{{ number_format($count) }}</span>
                    <span class="pp-stage__fine">{{ $fineprint[$stage['key']] ?? '' }}</span>
                </button>
            @endforeach
        </div>

        {{-- Table --}}
        <div class="pp-panel">
            {{-- Toolbar / bulk bar --}}
            @if (! empty($selected))
                <div class="ui-bulk-bar" style="border-radius:0">
                    <div>
                        {{ count($selected) }} selected
                    </div>
                    <div class="ui-bulk-bar__actions">
                        <button type="button" wire:click="emailSelected"
                                class="pp-btn pp-btn--sm pp-btn--pri">Email selected</button>
                        <button type="button" wire:click="$set('selected', [])"
                                class="pp-btn pp-btn--sm pp-btn--ghost" style="color:#fff">Clear</button>
                    </div>
                </div>
            @else
                <div class="pp-tbar">
                    <div class="pp-tbar__title">{{ $this->stageIndexLabel() }}</div>
                    @if (! empty($stuckIds))
                        <span class="pp-pill pp-pill--wa">{{ count($stuckIds) }} stuck &gt; 7 days</span>
                    @endif
                    <div class="pp-spacer" style="flex:1"></div>
                    <button type="button" wire:click="toggleSort"
                            class="pp-btn pp-btn--sm pp-btn--ghost"
                            title="Sort by longest waiting">
                        @if ($longestFirst)
                            Sorted longest waiting ↓
                        @else
                            Sort longest waiting
                        @endif
                    </button>
                </div>
            @endif

            @if ($rows->isEmpty())
                <div style="padding:2rem 1rem;text-align:center;color:var(--ui-ink-2);font-size:0.8125rem">
                    <div style="color:var(--ui-good);font-weight:500;margin-bottom:0.25rem">Nothing waiting at this stage</div>
                    Once new signups reach this stage they will land here — sorted longest-waiting first.
                </div>
            @else
                <div class="pp-tw">
                    <table class="pp-table" aria-label="{{ $this->stageIndexLabel() }}">
                        <thead>
                            <tr>
                                <th style="width:2.25rem"></th>
                                <th>Name</th>
                                <th>Email</th>
                                <th style="text-align:right">Signed up</th>
                                <th style="text-align:right">Stuck for</th>
                                <th style="text-align:right">Last nudge</th>
                                <th style="text-align:right">Next step</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $member)
                                @php
                                    $days = $member->daysInStage();
                                    $stuckClass = match (true) {
                                        $days === null => '',
                                        $days > 14 => ' pp-stuck--bad',
                                        $days > 7 => ' pp-stuck--mid',
                                        default => '',
                                    };
                                    $next = $this->nextStepFor($member);
                                    $lastNudge = $this->lastNudge($member);
                                    $stageLabel = $member->currentOnboardingStageLabel();
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox"
                                               wire:model.live="selected"
                                               value="{{ $member->id }}"
                                               aria-label="Select {{ $member->fullName() }}">
                                    </td>
                                    <td class="pp-td--name">
                                        <div class="pp-who">
                                            <a href="{{ $this->recordUrl($member) }}">{{ $member->fullName() }}</a>
                                        </div>
                                        @if ($stageLabel)
                                            <div class="sub" style="color:var(--ui-ink-3);font-size:0.75rem">{{ $stageLabel }}</div>
                                        @endif
                                    </td>
                                    <td class="pp-td--mono">
                                        @if ($member->hasPlaceholderEmail())
                                            <span class="pp-td--dim">(placeholder)</span>
                                        @else
                                            {{ $member->user?->email ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="pp-td--right pp-td--mono">
                                        {{ $member->created_at?->format('d M Y') ?? '—' }}
                                    </td>
                                    <td class="pp-td--right">
                                        @if ($days !== null)
                                            <span class="pp-stuck{{ $stuckClass }}">{{ $days }}d</span>
                                        @else
                                            <span class="pp-td--dim">—</span>
                                        @endif
                                    </td>
                                    <td class="pp-td--right pp-td--mono">
                                        {{ $lastNudge ?? '—' }}
                                    </td>
                                    <td class="pp-td--right">
                                        <div class="pp-row-act">
                                            @if ($next['method'])
                                                <button type="button"
                                                        wire:click="{{ $next['method'] }}({{ $member->id }})"
                                                        wire:loading.attr="disabled"
                                                        class="pp-btn pp-btn--sm pp-btn--pri">
                                                    {{ $next['label'] }}
                                                </button>
                                            @else
                                                <a href="{{ $this->recordUrl($member) }}" class="pp-btn pp-btn--sm">
                                                    {{ $next['label'] }}
                                                </a>
                                            @endif
                                            <a href="{{ $this->recordUrl($member) }}"
                                               class="pp-btn pp-btn--sm pp-btn--ghost pp-btn--icon"
                                               aria-label="Open record">⋯</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
