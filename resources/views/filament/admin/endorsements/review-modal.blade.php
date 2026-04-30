@php
    /** @var \App\Models\EndorsementRequest $record */
    $member = $record->member;
@endphp

<div class="space-y-5 text-sm">
    {{-- Member --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-3">Member</h3>
        <dl class="grid grid-cols-2 gap-3">
            <div>
                <dt class="text-xs text-gray-500">Name</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $member?->fullName() ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Membership #</dt>
                <dd class="font-mono text-gray-900 dark:text-gray-100">{{ $member?->membership_number ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Email</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $member?->user?->email ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Joined</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $member?->join_date?->format('j F Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Status</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $member?->status?->label() ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Active membership</dt>
                <dd class="text-gray-900 dark:text-gray-100">
                    {{ $member?->hasActiveMembership() ? 'Yes' : 'No' }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- Request --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-3">Request details</h3>
        <dl class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
                <dt class="text-xs text-gray-500">Purpose</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $record->reason ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Item type</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $record->isComponent() ? 'Component' : 'Complete rifle' }}</dd>
            </div>
            @if ($record->isComponent())
                <div>
                    <dt class="text-xs text-gray-500">Component</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $record->component_type ?: '—' }}</dd>
                </div>
            @else
                <div>
                    <dt class="text-xs text-gray-500">Action type</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $record->firearm_type ?: '—' }}</dd>
                </div>
            @endif
            <div>
                <dt class="text-xs text-gray-500">Make / brand</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $record->make ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Calibre</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $record->calibre ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Action serial</dt>
                <dd class="text-gray-900 dark:text-gray-100 font-mono">{{ $record->action_serial_number ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Barrel serial</dt>
                <dd class="text-gray-900 dark:text-gray-100 font-mono">{{ $record->barrel_serial_number ?: '—' }}</dd>
            </div>
            @if ($record->firearm_details)
                <div class="col-span-2">
                    <dt class="text-xs text-gray-500">Additional details</dt>
                    <dd class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $record->firearm_details }}</dd>
                </div>
            @endif
            @if ($member?->id_number)
                <div>
                    <dt class="text-xs text-gray-500">ID number</dt>
                    <dd class="text-gray-900 dark:text-gray-100 font-mono">{{ $member->id_number }}</dd>
                </div>
            @endif
            <div>
                <dt class="text-xs text-gray-500">Submitted</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $record->created_at?->format('j F Y H:i') }}</dd>
            </div>
        </dl>
    </div>

    {{-- Status --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-3">Status</h3>
        <dl class="grid grid-cols-2 gap-3">
            <div>
                <dt class="text-xs text-gray-500">Current status</dt>
                <dd>
                    @php($color = match($record->status->color()) {
                        'success' => 'bg-green-100 text-green-700',
                        'warning' => 'bg-amber-100 text-amber-700',
                        'danger' => 'bg-red-100 text-red-700',
                        default => 'bg-gray-100 text-gray-700',
                    })
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $color }}">
                        {{ $record->status->label() }}
                    </span>
                </dd>
            </div>
            @if ($record->reviewed_at)
            <div>
                <dt class="text-xs text-gray-500">Reviewed</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $record->reviewed_at->format('j F Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Reviewed by</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $record->reviewedBy?->name ?? '—' }}</dd>
            </div>
            @endif
            @if ($record->admin_notes)
            <div class="col-span-2">
                <dt class="text-xs text-gray-500">Admin notes</dt>
                <dd class="text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $record->admin_notes }}</dd>
            </div>
            @endif
        </dl>
    </div>
</div>
