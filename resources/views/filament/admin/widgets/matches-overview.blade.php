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
            <a href="{{ $data['draftsUrl'] }}"
               aria-label="{{ $data['drafts'] > 0 ? $data['drafts'].' draft matches — view list' : 'Draft matches: none — view list' }}"
               @class([
                   'flex flex-col items-center justify-center rounded-xl border p-4 text-center transition hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900',
                   'border-gray-200 hover:border-warning-300 dark:border-white/10 dark:hover:border-warning-500/30' => $data['drafts'] > 0,
                   'border-dashed border-gray-200 hover:border-gray-300 dark:border-white/10' => $data['drafts'] === 0,
               ])>
                <p class="text-2xl font-bold tabular-nums {{ $data['drafts'] > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-300 dark:text-gray-600' }}">
                    {{ $data['drafts'] }}
                </p>
                <p class="mt-1 text-xs font-medium text-gray-500">Draft matches</p>
                <p class="mt-0.5 text-[11px] text-gray-400 dark:text-gray-500">
                    {{ $data['drafts'] > 0 ? 'Not published yet' : 'None in progress' }}
                </p>
            </a>

            <a href="{{ $data['awaitingResultsUrl'] }}"
               aria-label="{{ $data['awaitingResults'] > 0 ? $data['awaitingResults'].' matches awaiting results — view list' : 'Awaiting results: none — view list' }}"
               @class([
                   'flex flex-col items-center justify-center rounded-xl border p-4 text-center transition hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900',
                   'border-gray-200 hover:border-warning-300 dark:border-white/10 dark:hover:border-warning-500/30' => $data['awaitingResults'] > 0,
                   'border-dashed border-gray-200 hover:border-gray-300 dark:border-white/10' => $data['awaitingResults'] === 0,
               ])>
                <p class="text-2xl font-bold tabular-nums {{ $data['awaitingResults'] > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-300 dark:text-gray-600' }}">
                    {{ $data['awaitingResults'] }}
                </p>
                <p class="mt-1 text-xs font-medium text-gray-500">Awaiting results</p>
                <p class="mt-0.5 text-[11px] text-gray-400 dark:text-gray-500">
                    {{ $data['awaitingResults'] > 0 ? 'Ready to publish' : 'All caught up' }}
                </p>
            </a>
        </div>

        {{-- All other upcoming matches (the soonest is shown as the hero above) --}}
        @if (count($data['upcoming']) > 1)
            <div class="mt-4 border-t border-gray-100 pt-4 dark:border-white/5">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    More upcoming ({{ count($data['upcoming']) - 1 }})
                </p>
                <ul role="list" class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach (array_slice($data['upcoming'], 1) as $match)
                        <li>
                            <a href="{{ $match['url'] }}"
                               aria-label="{{ $match['title'].' — '.$match['date'].', '.$match['registrationCount'].' registered' }}"
                               class="-mx-2 flex items-center justify-between gap-3 rounded-lg px-2 py-2.5 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:bg-white/5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $match['title'] }}</p>
                                    <p class="mt-0.5 inline-flex items-center gap-1 text-xs text-gray-500">
                                        <x-heroicon-o-calendar class="h-3.5 w-3.5" aria-hidden="true" />
                                        {{ $match['date'] }}
                                    </p>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1 text-xs text-gray-500">
                                    <x-heroicon-o-users class="h-3.5 w-3.5" aria-hidden="true" />
                                    {{ $match['registrationCount'] }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
