@props([
    'variant' => 'muted',   // ok | warn | crit | info | muted
    'plain' => false,        // hide the dot
    'icon' => null,
])

{{--
    Status pill. Status is never colour alone — dot + label pattern.
    Use `variant` to pick a semantic colour; never overload `crit` for
    destructive buttons.
--}}

@php
    $variantClass = match ($variant) {
        'ok', 'good' => 'ui-pill--ok',
        'warn' => 'ui-pill--warn',
        'crit', 'danger' => 'ui-pill--crit',
        'info', 'accent' => 'ui-pill--info',
        default => 'ui-pill--muted',
    };
@endphp

<span {{ $attributes->class(['ui-pill', $variantClass, 'ui-pill--plain' => $plain]) }}>
    @if ($icon)
        <span class="ui-pill__icon">{!! $icon !!}</span>
    @endif
    <span>{{ $slot }}</span>
</span>
