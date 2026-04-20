@props([
    'tone' => 'default',
    'padding' => 'default',
    'id' => null,
    'width' => 'default',
])
@php
    // Tone → surface colour. Dark is the default theme but we still split so
    // adjacent sections can alternate subtly without looking busy.
    $toneClasses = match ($tone) {
        'base'    => 'bg-slate-950 text-white',
        'raised'  => 'bg-slate-900 text-white',
        'muted'   => 'bg-white/[0.02] text-white border-y border-white/10',
        'accent'  => 'bg-white text-slate-950',
        default   => 'bg-slate-950 text-white',
    };

    $paddingClasses = match ($padding) {
        'hero'   => 'py-24 sm:py-32 lg:py-40',
        'lg'     => 'py-20 sm:py-28',
        'sm'     => 'py-12 sm:py-16',
        default  => 'py-16 sm:py-24',
    };
@endphp
<section @if($id) id="{{ $id }}" @endif {{ $attributes->class(['relative isolate', $toneClasses]) }}>
    <x-site.container :width="$width" class="{{ $paddingClasses }}">
        {{ $slot }}
    </x-site.container>
</section>
