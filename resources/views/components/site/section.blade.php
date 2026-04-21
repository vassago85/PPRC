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
        /** Large top for match hero; tighter bottom before the next section (e.g. registration). */
        'lg-hero' => 'pt-20 sm:pt-28 pb-8 sm:pb-10',
        /** Tight top when stacked directly under match hero / description. */
        'register' => 'pt-8 sm:pt-10 pb-16 sm:pb-24',
        /** Match page: hero + stats + description + registration in one band (avoids double section gaps). */
        'match-main' => 'pt-20 sm:pt-28 pb-12 sm:pb-16',
        'sm'     => 'py-12 sm:py-16',
        default  => 'py-16 sm:py-24',
    };
@endphp
<section @if($id) id="{{ $id }}" @endif {{ $attributes->class(['relative isolate', $toneClasses]) }}>
    <x-site.container :width="$width" class="{{ $paddingClasses }}">
        {{ $slot }}
    </x-site.container>
</section>
