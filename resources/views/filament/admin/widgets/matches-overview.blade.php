@php $data = $this->getData(); @endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-calendar-days class="h-5 w-5 text-info-500" />
                Matches
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            <div class="flex items-center gap-2">
                <x-filament::link :href="$data['createUrl']" icon="heroicon-o-plus" size="sm">
                    Create match
                </x-filament::link>
                <x-filament::link :href="$data['listUrl']" icon="heroicon-o-list-bullet" size="sm">
                    View all
                </x-filament::link>
            </div>
        </x-slot>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Next match card --}}
            <div class="col-span-full rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-white/10 dark:bg-white/[0.02] sm:col-span-2">
                @if ($data['next'])
                    <a href="{{ $data['next']['url'] }}" class="block">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Next match</p>
                        <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $data['next']['title'] }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-calendar class="h-4 w-4" />
                                {{ $data['next']['date'] }}
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-users class="h-4 w-4" />
                                {{ $data['next']['registrationCount'] }} registered
                            </span>
                        </div>
                    </a>
                @else
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Next match</p>
                    <p class="mt-2 text-sm text-gray-500">No upcoming matches scheduled.</p>
                @endif
            </div>

            {{-- Stat pills --}}
            <a href="{{ $data['draftsUrl'] }}" class="flex flex-col items-center justify-center rounded-xl border border-gray-200 p-4 text-center transition hover:border-warning-300 hover:shadow-sm dark:border-white/10 dark:hover:border-warning-500/30">
                <p class="text-2xl font-bold tabular-nums {{ $data['drafts'] > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-400' }}">
                    {{ $data['drafts'] }}
                </p>
                <p class="mt-1 text-xs font-medium text-gray-500">Draft matches</p>
            </a>

            <a href="{{ $data['awaitingResultsUrl'] }}" class="flex flex-col items-center justify-center rounded-xl border border-gray-200 p-4 text-center transition hover:border-warning-300 hover:shadow-sm dark:border-white/10 dark:hover:border-warning-500/30">
                <p class="text-2xl font-bold tabular-nums {{ $data['awaitingResults'] > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-400' }}">
                    {{ $data['awaitingResults'] }}
                </p>
                <p class="mt-1 text-xs font-medium text-gray-500">Awaiting results</p>
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
