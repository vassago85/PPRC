@props([
    'question',
    'id' => null,
])
@php
    // Stable, slug-based identifier for the Alpine `open` flag. Falls back to
    // a hashed question so duplicate questions across groups don't collide.
    $itemId = $id ?? \Illuminate\Support\Str::slug($question);
    if ($itemId === '') {
        $itemId = 'faq-'.substr(md5($question), 0, 8);
    }
    $panelId = 'faq-panel-'.$itemId;
    $buttonId = 'faq-button-'.$itemId;
@endphp
{{--
    Single FAQ entry. Must be nested inside <x-site.faq-group> which provides
    the Alpine `open` state. The button toggles `open` between this item's id
    and null, so only one item per group is ever expanded. The panel animates
    via Alpine's built-in transitions (no @alpinejs/collapse dependency).
--}}
<div class="group/faq">
    <h3>
        <button
            type="button"
            id="{{ $buttonId }}"
            aria-controls="{{ $panelId }}"
            :aria-expanded="open === '{{ $itemId }}' ? 'true' : 'false'"
            @click="open = (open === '{{ $itemId }}') ? null : '{{ $itemId }}'"
            class="flex w-full items-start justify-between gap-6 px-5 py-5 text-left transition sm:px-6 sm:py-6 hover:bg-white/[0.03] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400/60 focus-visible:ring-offset-0"
        >
            <span class="text-base font-medium leading-snug text-white sm:text-lg">
                {{ $question }}
            </span>
            <span
                class="relative mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-white/15 bg-white/[0.03] text-slate-300 transition"
                :class="{ 'bg-brand-500/15 border-brand-400/40 text-brand-200': open === '{{ $itemId }}' }"
                aria-hidden="true"
            >
                <svg
                    viewBox="0 0 20 20"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="h-3.5 w-3.5 transition-transform duration-200"
                    :class="{ 'rotate-180': open === '{{ $itemId }}' }"
                >
                    <path d="m5 7.5 5 5 5-5" />
                </svg>
            </span>
        </button>
    </h3>

    <div
        id="{{ $panelId }}"
        role="region"
        aria-labelledby="{{ $buttonId }}"
        x-show="open === '{{ $itemId }}'"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="px-5 pb-6 sm:px-6 sm:pb-7"
    >
        <div class="max-w-3xl space-y-4 text-[15px] leading-relaxed text-slate-300 [&_p]:text-slate-300 [&_ul]:list-disc [&_ul]:space-y-2 [&_ul]:pl-5 [&_ul]:marker:text-brand-300 [&_li]:text-slate-300 [&_strong]:text-white">
            {{ $slot }}
        </div>
    </div>
</div>
