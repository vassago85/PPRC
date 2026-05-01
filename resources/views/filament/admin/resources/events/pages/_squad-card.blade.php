@php
    /** @var \App\Models\EventRegistration $entry */
    /** @var \App\Models\Event $event */
    $name = $entry->shooterName();
    $division = $entry->division;
    $courseLabel = $event->courseLabel($entry->course);
    $rounds = $event->roundsForCourse($entry->course);
@endphp
<div
    class="squad-card cursor-grab rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm transition hover:border-primary-400 active:cursor-grabbing dark:border-white/10 dark:bg-gray-800"
    data-entry-id="{{ $entry->id }}"
>
    <div class="flex items-baseline gap-2">
        <span class="font-medium text-gray-900 dark:text-white">{{ $name }}</span>
        @if ($entry->is_junior)
            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wider text-amber-800 dark:bg-amber-500/20 dark:text-amber-200">Jr</span>
        @endif
        @if ($entry->is_saprf_entry)
            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wider text-amber-800 dark:bg-amber-500/20 dark:text-amber-200">SAPRF</span>
        @endif
    </div>
    <div class="mt-0.5 flex flex-wrap items-baseline gap-x-1.5 text-xs text-gray-500 dark:text-gray-400">
        @if ($division)
            <span class="uppercase tracking-wider">{{ $division }}</span>
        @endif
        @if ($courseLabel)
            <span>·</span>
            <span>{{ $courseLabel }}</span>
        @endif
        @if ($rounds)
            <span>·</span>
            <span class="tabular-nums">{{ $rounds }} rds</span>
        @endif
    </div>
</div>
