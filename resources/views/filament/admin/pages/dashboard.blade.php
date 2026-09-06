@php
    /** @var \App\Services\Admin\AdminDashboardService $svc */
    $svc = app(\App\Services\Admin\AdminDashboardService::class);
    $needs = $svc->needsYou();
    $money = $svc->moneyStrip();
    $next = $svc->nextMatch();
    $activity = $svc->groupedActivity();
@endphp

<x-filament-panels::page>
    <x-ui.page>
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            {{-- Left column: Needs you + Money + Next match --}}
            <div class="xl:col-span-2 flex flex-col gap-6">
                <x-ui.panel title="Needs you">
                    @if (empty($needs))
                        <x-ui.empty
                            icon="heroicon-o-check-circle"
                            title="All clear"
                            description="Nothing outstanding right now. New payments, onboarding stalls and unpublished results will show up here as they land."
                        />
                    @else
                        <ol class="divide-y divide-[color:var(--ui-line)]">
                            @foreach ($needs as $row)
                                @php
                                    $accent = match ($row['urgency']) {
                                        'crit' => 'var(--ui-crit)',
                                        'warn' => 'var(--ui-warn)',
                                        default => 'var(--ui-accent)',
                                    };
                                @endphp
                                <li class="grid grid-cols-[3.25rem_1fr_auto] items-center gap-3 py-3 first:pt-0 last:pb-0"
                                    style="border-left: 3px solid {{ $accent }}; padding-left: 0.875rem;">
                                    <div class="ui-cell--mono text-xl font-semibold text-[color:var(--ui-ink)]">
                                        {{ number_format($row['count']) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm text-[color:var(--ui-ink)]">{{ $row['label'] }}</div>
                                        @if (! empty($row['context']))
                                            <div class="text-xs text-[color:var(--ui-ink-3)]">{{ $row['context'] }}</div>
                                        @endif
                                    </div>
                                    <a href="{{ $row['action_url'] }}" class="ui-btn ui-btn--sm">
                                        {{ $row['action_label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </x-ui.panel>

                {{-- Money strip --}}
                <x-ui.panel title="Money">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        @foreach ($money as $cell)
                            <div>
                                <div class="ui-panel__title">{{ $cell['label'] }}</div>
                                <div class="mt-1 text-2xl font-semibold text-[color:var(--ui-ink)] ui-cell--mono">
                                    {{ $cell['formatted'] }}
                                </div>
                                <div class="mt-1 text-xs text-[color:var(--ui-ink-3)]">{{ $cell['context'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.panel>

                {{-- Next match — a real object, not a tile --}}
                @if ($next)
                    <x-ui.panel title="Next match">
                        <div class="flex flex-col gap-3">
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <h3 class="text-lg font-semibold text-[color:var(--ui-ink)]">{{ $next['title'] }}</h3>
                                <span class="text-sm text-[color:var(--ui-ink-2)] ui-cell--mono">{{ $next['date'] }}</span>
                                @if ($next['venue'])
                                    <span class="text-sm text-[color:var(--ui-ink-2)]">· {{ $next['venue'] }}</span>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                <div>
                                    <div class="ui-panel__title">Entries</div>
                                    @if ($next['max_entries'])
                                        <div class="mt-1"><x-ui.meter :value="$next['entries']" :max="$next['max_entries']" /></div>
                                    @else
                                        <div class="ui-cell--mono text-lg mt-1">{{ $next['entries'] }}</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="ui-panel__title">New this week</div>
                                    <div class="ui-cell--mono text-lg mt-1">+{{ $next['new_this_week'] }}</div>
                                </div>
                                <div>
                                    <div class="ui-panel__title">Unpaid entries</div>
                                    <div class="ui-cell--mono text-lg mt-1">{{ $next['unpaid_entries'] }}</div>
                                </div>
                                <div>
                                    <div class="ui-panel__title">Entries close</div>
                                    <div class="ui-cell--mono text-sm mt-1">
                                        {{ $next['entries_close_at']?->format('D d M') ?? '—' }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <a href="{{ $next['squad_url'] }}" class="ui-btn ui-btn--primary ui-btn--sm">Squadding</a>
                                @if ($next['match_book_url'])
                                    <a href="{{ $next['match_book_url'] }}" class="ui-btn ui-btn--sm" target="_blank" rel="noopener">Match book</a>
                                @endif
                                <a href="{{ $next['report_url'] }}" class="ui-btn ui-btn--sm">Entry list</a>
                                <a href="{{ $next['edit_url'] }}" class="ui-btn ui-btn--sm ui-btn--ghost">Edit</a>
                            </div>
                        </div>
                    </x-ui.panel>
                @endif
            </div>

            {{-- Right column: Activity feed --}}
            <div class="flex flex-col gap-6">
                <x-ui.panel title="Activity">
                    <div class="space-y-4">
                        @foreach (['today' => 'Today', 'this_week' => 'This week', 'earlier' => 'Earlier'] as $key => $groupLabel)
                            @if (! empty($activity[$key]))
                                <div>
                                    <div class="ui-panel__title mb-2">{{ $groupLabel }}</div>
                                    <ul class="space-y-2">
                                        @foreach ($activity[$key] as $item)
                                            <li class="flex items-start gap-2 text-sm">
                                                <x-dynamic-component :component="$item['icon']" class="w-4 h-4 mt-0.5 text-[color:var(--ui-ink-3)]" />
                                                <div class="min-w-0 flex-1">
                                                    @if (! empty($item['url']))
                                                        <a href="{{ $item['url'] }}" class="hover:underline">{{ $item['description'] }}</a>
                                                    @else
                                                        {{ $item['description'] }}
                                                    @endif
                                                    <div class="text-xs text-[color:var(--ui-ink-3)] ui-cell--mono">
                                                        {{ \Illuminate\Support\Carbon::parse($item['timestamp'])->diffForHumans() }}
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endforeach

                        @if (empty($activity['today']) && empty($activity['this_week']) && empty($activity['earlier']))
                            <x-ui.empty
                                icon="heroicon-o-clock"
                                title="No recent activity"
                                description="Signups, payments, and match events will appear here."
                            />
                        @endif
                    </div>

                    <x-slot:foot>
                        <div class="flex items-center gap-4 text-xs text-[color:var(--ui-ink-3)]">
                            <span class="inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-[color:var(--ui-good)]"></span> Money
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-[color:var(--ui-accent)]"></span> Members
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-[color:var(--ui-warn)]"></span> Matches
                            </span>
                        </div>
                    </x-slot:foot>
                </x-ui.panel>
            </div>
        </div>
    </x-ui.page>
</x-filament-panels::page>
