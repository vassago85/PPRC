@props([
    'value' => 0,
    'max' => 100,
    'label' => null,
    'showLabel' => true,
])

{{--
    Entries-against-capacity meter. 6-px bar, tabular-nums caption.

    Variants applied by ratio:
        <60%  → accent (default)
        60-89 → accent
        90-99 → warn
        100%+ → good (full/at capacity)
--}}

@php
    $val = max(0, (int) $value);
    $mx = max(1, (int) $max);
    $ratio = min(1.2, $val / $mx);
    $pct = min(100, round($ratio * 100));
    $variant = match (true) {
        $val >= $mx => 'ui-meter--full',
        $ratio >= 0.9 => 'ui-meter--warn',
        default => '',
    };
    $labelText = $label ?? "{$val} / {$mx}";
@endphp

<span {{ $attributes->class(['ui-meter', $variant]) }} role="progressbar"
    aria-valuenow="{{ $val }}" aria-valuemin="0" aria-valuemax="{{ $mx }}">
    <span class="ui-meter__track">
        <span class="ui-meter__fill" style="width: {{ $pct }}%"></span>
    </span>
    @if ($showLabel)
        <span class="ui-meter__label">{{ $labelText }}</span>
    @endif
</span>
