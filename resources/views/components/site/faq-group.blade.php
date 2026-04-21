@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])
{{--
    FAQ group: a titled region with a stack of collapsible <x-site.faq-item>s.
    The Alpine `open` state lives on this wrapper so only one item per group
    can be expanded at a time. Items toggle via `open === '<slug-id>'`.
--}}
<div
    role="region"
    aria-label="{{ $title }}"
    x-data="{ open: null }"
    {{ $attributes->class(['space-y-6']) }}
>
    <header class="max-w-2xl">
        @if ($eyebrow)
            <x-site.eyebrow>{{ $eyebrow }}</x-site.eyebrow>
        @endif
        <h2 class="mt-3 text-2xl font-semibold tracking-tight text-white sm:text-3xl">
            {{ $title }}
        </h2>
        @if ($description)
            <p class="mt-3 text-slate-400">{{ $description }}</p>
        @endif
    </header>

    <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02] divide-y divide-white/5 motion-safe:transition motion-safe:duration-300 motion-safe:hover:border-white/20 motion-safe:hover:shadow-[0_24px_60px_-28px_rgba(0,0,0,0.45)] motion-reduce:hover:shadow-none">
        {{ $slot }}
    </div>
</div>
