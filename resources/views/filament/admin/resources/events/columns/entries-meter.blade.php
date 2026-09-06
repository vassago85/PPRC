@php
    /** @var \App\Models\Event $record */
    $count = (int) ($record->registrations_count ?? 0);
    $max = (int) ($record->max_entries ?? 0);
    $state = \App\Enums\EventState::for($record);

    // When results are overdue, the entry count is stale info — call it out.
    $overdue = $state === \App\Enums\EventState::Shot
        && $record->start_date
        && $record->start_date->lt(now()->subDays(7));
    $daysAgo = $overdue && $record->start_date
        ? (int) now()->diffInDays($record->start_date, false) * -1
        : null;
@endphp

<div class="flex items-center gap-2">
    @if ($overdue)
        <span class="ui-pill ui-pill--crit">
            <span>Results overdue · shot {{ $daysAgo }} days ago</span>
        </span>
    @elseif ($max > 0)
        <x-ui.meter :value="$count" :max="$max" />
    @else
        <span class="ui-money">{{ $count }}</span>
    @endif
</div>
