@php $items = $this->getItems(); @endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-clock class="h-5 w-5 text-gray-400" />
                Recent activity
            </div>
        </x-slot>

        @if (count($items))
            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($items as $item)
                    <a href="{{ $item['url'] }}"
                       class="flex items-center gap-3 px-1 py-2.5 transition hover:bg-gray-50 dark:hover:bg-white/5 {{ $loop->first ? '' : '' }}">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400">
                            <x-filament::icon :icon="$item['icon']" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-gray-700 dark:text-gray-300">{{ $item['description'] }}</p>
                        </div>
                        <time class="shrink-0 text-xs text-gray-400" datetime="{{ $item['timestamp']->toISOString() }}">
                            {{ $item['timestamp']->diffForHumans(short: true) }}
                        </time>
                    </a>
                @endforeach
            </div>
        @else
            <p class="py-4 text-center text-sm text-gray-500">No recent activity.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
