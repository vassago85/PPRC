@props([
    'cents' => null,
    'amount' => null,
    'currency' => 'R',
    'muted' => false,
    'placeholder' => '—',
])

{{--
    Renders ZAR in mono, tabular, right-aligned in tables.

    Pass either `cents` (integer, preferred) or `amount` (float rand value).
    Both null → `placeholder`.
--}}

@php
    if ($cents !== null) {
        $value = ((int) $cents) / 100;
    } elseif ($amount !== null) {
        $value = (float) $amount;
    } else {
        $value = null;
    }
@endphp

<span {{ $attributes->class(['ui-money', 'ui-money--muted' => $muted]) }}>
    @if ($value === null)
        {{ $placeholder }}
    @else
        {{ $currency }} {{ number_format($value, 2, '.', ',') }}
    @endif
</span>
