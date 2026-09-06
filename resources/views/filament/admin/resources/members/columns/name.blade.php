@php
    /** @var \App\Models\Member $record */
    $isPlaceholder = $record->hasPlaceholderEmail();
    $email = $record->user?->email;
    $linkedAdult = $record->linkedAdult;
    $isJunior = $record->isJunior();
@endphp

<div class="flex flex-col gap-0.5 min-w-0">
    <div class="flex items-center gap-1.5 min-w-0">
        <span class="ui-cell__label truncate">{{ $record->fullName() }}</span>

        @if ($isJunior)
            <span class="ui-chip">Junior</span>
        @endif

        @if ($record->user?->isCommittee())
            <span class="ui-chip">Committee</span>
        @endif

        @if ($record->user?->hasRole('match_director'))
            <span class="ui-chip">Match director</span>
        @endif
    </div>

    @if ($isPlaceholder)
        {{-- Junior / spouse placeholder — never show the ugly UUID mailbox. --}}
        <span class="ui-cell__sub">
            @if ($linkedAdult)
                linked to {{ $linkedAdult->fullName() }}
            @else
                linked member
            @endif
        </span>
    @elseif ($email)
        <span class="ui-cell__sub truncate">{{ $email }}</span>
    @endif
</div>
