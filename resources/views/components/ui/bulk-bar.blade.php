@props([
    'count' => 0,
    'noun' => 'row',
])

{{--
    Replaces the panel head in-place when rows are selected. If a list has
    checkboxes and no bulk actions, drop the checkboxes instead of shipping
    an unusable bar.

    Slot: bulk action buttons (right-aligned).
--}}

@if ($count > 0)
    <div {{ $attributes->class('ui-bulk-bar') }} role="region" aria-label="Bulk actions">
        <div>
            <strong>{{ number_format((int) $count) }}</strong>
            {{ \Illuminate\Support\Str::plural($noun, (int) $count) }} selected
        </div>
        <div class="ui-bulk-bar__actions">
            {{ $slot }}
        </div>
    </div>
@endif
