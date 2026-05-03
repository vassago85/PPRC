<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-bell-alert class="h-5 w-5 text-warning-500" />
                Needs attention
            </div>
        </x-slot>

        @if ($this->hasActiveItems())
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($this->getItems() as $item)
                    @if ($item['value'] > 0)
                        <a href="{{ $item['url'] }}"
                           class="group flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 transition hover:border-primary-300 hover:shadow-sm dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-500/30">
                            <div @class([
                                'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
                                'bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400' => $item['color'] === 'warning',
                                'bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400' => $item['color'] === 'danger',
                                'bg-info-50 text-info-600 dark:bg-info-500/10 dark:text-info-400' => $item['color'] === 'info',
                                'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400' => $item['color'] === 'success',
                                'bg-gray-50 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400' => $item['color'] === 'gray',
                            ])>
                                <x-filament::icon :icon="$item['icon']" class="h-5 w-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-2xl font-bold tabular-nums text-gray-950 dark:text-white">
                                    {{ number_format($item['value']) }}
                                </p>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ $item['label'] }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-500">
                                    {{ $item['description'] }}
                                </p>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        @else
            <div class="flex items-center gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50/50 p-6 dark:border-gray-700 dark:bg-gray-800/30">
                <x-heroicon-o-check-circle class="h-8 w-8 text-success-500" />
                <div>
                    <p class="font-medium text-gray-950 dark:text-white">Nothing needs attention</p>
                    <p class="text-sm text-gray-500">All queues are clear.</p>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
