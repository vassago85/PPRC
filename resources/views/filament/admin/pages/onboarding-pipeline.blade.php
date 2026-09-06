@php
    $counts = $this->stageCounts();
    $rows = $this->rows();
    $totalPending = array_sum(array_diff_key($counts, ['abandoned' => null]));

    $stages = [
        ['key' => 'email_unconfirmed', 'label' => 'Email unconfirmed'],
        ['key' => 'choosing_plan',     'label' => 'Choosing plan'],
        ['key' => 'awaiting_payment',  'label' => 'Awaiting payment'],
        ['key' => 'ready_to_activate', 'label' => 'Ready to activate'],
        ['key' => 'abandoned',         'label' => 'Abandoned'],
    ];
@endphp

<x-filament-panels::page>
    <x-ui.page>
        {{-- Pipeline bar --}}
        <x-ui.panel title="Pipeline">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($stages as $stage)
                    @php $count = $counts[$stage['key']] ?? 0; @endphp
                    <button
                        type="button"
                        wire:click="setStage('{{ $stage['key'] }}')"
                        @class([
                            'flex flex-col items-start gap-1 rounded-md border p-3 text-left transition hover:bg-[color:var(--ui-surface-2)]',
                            'border-[color:var(--ui-accent)] bg-[color:var(--ui-accent-soft)]' => $this->stage === $stage['key'],
                            'border-[color:var(--ui-line)]' => $this->stage !== $stage['key'],
                        ])
                    >
                        <span class="text-xs uppercase tracking-wide text-[color:var(--ui-ink-2)] ui-panel__title">
                            {{ $stage['label'] }}
                        </span>
                        <span class="text-2xl font-semibold text-[color:var(--ui-ink)] ui-cell--mono">
                            {{ number_format($count) }}
                        </span>
                    </button>
                @endforeach
            </div>
            <div class="mt-3 flex items-center gap-3 text-sm text-[color:var(--ui-ink-2)]">
                <button type="button" wire:click="setStage('all')"
                    class="ui-btn ui-btn--sm ui-btn--ghost">All pending</button>
                <span>{{ $totalPending }} members currently in the pipeline</span>
            </div>
        </x-ui.panel>

        {{-- Table --}}
        <x-ui.panel flush>
            @if ($rows->isEmpty())
                <x-ui.empty
                    icon="heroicon-o-check-circle"
                    title="Nothing waiting at this stage"
                    description="Once new signups reach this stage they will appear here."
                />
            @else
                <x-ui.table :columns="[
                    ['label' => 'Name'],
                    ['label' => 'Email'],
                    ['label' => 'Signed up', 'align' => 'right'],
                    ['label' => 'Stuck for', 'align' => 'right'],
                    ['label' => 'Last nudge', 'align' => 'right'],
                    ['label' => 'Next step', 'align' => 'right'],
                ]">
                    @foreach ($rows as $member)
                        @php
                            $days = $member->daysInStage();
                            $stuckVariant = match (true) {
                                $days === null => 'muted',
                                $days > 14 => 'crit',
                                $days > 7 => 'warn',
                                default => 'muted',
                            };
                            $next = $this->nextStepFor($member);
                            $lastNudge = $this->lastNudge($member);
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ $this->recordUrl($member) }}" class="ui-cell__label hover:underline">
                                    {{ $member->fullName() }}
                                </a>
                                <span class="ui-cell__sub">{{ $member->currentOnboardingStageLabel() ?? '—' }}</span>
                            </td>
                            <td class="ui-cell--mono text-sm">
                                @if ($member->hasPlaceholderEmail())
                                    <span class="text-[color:var(--ui-ink-3)]">(placeholder)</span>
                                @else
                                    {{ $member->user?->email ?? '—' }}
                                @endif
                            </td>
                            <td class="ui-cell--right ui-cell--mono">
                                {{ $member->created_at?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="ui-cell--right">
                                @if ($days !== null)
                                    <x-ui.pill :variant="$stuckVariant">{{ $days }} {{ \Illuminate\Support\Str::plural('day', $days) }}</x-ui.pill>
                                @else
                                    <span class="text-[color:var(--ui-ink-3)]">—</span>
                                @endif
                            </td>
                            <td class="ui-cell--right ui-cell--mono">
                                {{ $lastNudge ?? '—' }}
                            </td>
                            <td class="ui-cell--right">
                                @if ($next['method'])
                                    <button type="button"
                                        wire:click="{{ $next['method'] }}({{ $member->id }})"
                                        wire:loading.attr="disabled"
                                        class="ui-btn ui-btn--sm ui-btn--primary">
                                        {{ $next['label'] }}
                                    </button>
                                @else
                                    <a href="{{ $this->recordUrl($member) }}" class="ui-btn ui-btn--sm">
                                        {{ $next['label'] }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.panel>
    </x-ui.page>
</x-filament-panels::page>
