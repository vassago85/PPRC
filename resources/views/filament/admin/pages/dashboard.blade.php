@php
    /** @var \App\Services\Admin\AdminDashboardService $svc */
    $svc = app(\App\Services\Admin\AdminDashboardService::class);
    $needs = $svc->needsYou();
    $money = $svc->moneyStrip();
    $next = $svc->nextMatch();
    $activity = $svc->groupedActivity();

    // Oldest-item summary for the panel head — the prototype reads
    // "6 items · oldest 24 days" so the reader knows this is a real queue,
    // not a snapshot.
    $needsSummary = null;
    if (! empty($needs)) {
        $count = count($needs);
        $needsSummary = "{$count} item".($count === 1 ? '' : 's');
    }
@endphp

<x-filament-panels::page>
    <div class="pp-page">
        <div class="pp-phead">
            <div>
                <h1>Overview</h1>
                <div class="pp-sub">{!! $this->overviewSubtitle() !!}</div>
            </div>
        </div>

        <div class="pp-cols">
            {{-- LEFT COLUMN: queue, money strip, next match --}}
            <div style="display:flex;flex-direction:column;gap:1rem;min-width:0">

                {{-- Needs you --}}
                <div class="pp-panel">
                    <div class="pp-panel-head">
                        <h2>Needs you</h2>
                        @if ($needsSummary)
                            <span class="pp-lab" style="margin:0">&middot; {{ $needsSummary }}</span>
                        @endif
                        <div class="pp-spacer"></div>
                    </div>

                    @if (empty($needs))
                        <div style="padding:1.75rem 1rem;text-align:center;color:var(--ui-ink-3);font-size:0.8125rem">
                            <div style="color:var(--ui-good);font-weight:500;margin-bottom:0.25rem">All clear</div>
                            Nothing outstanding right now. New payments, onboarding stalls and unpublished results will show up here as they land.
                        </div>
                    @else
                        <div class="pp-queue">
                            @foreach ($needs as $row)
                                <div class="pp-q pp-q--{{ $row['urgency'] }}">
                                    <div class="pp-q__n">{{ number_format($row['count']) }}</div>
                                    <div class="pp-q__body">
                                        <div class="pp-q__title">{{ $row['label'] }}</div>
                                        @if (! empty($row['meta']))
                                            <div class="pp-q__meta">{{ $row['meta'] }}</div>
                                        @endif
                                        @if (! empty($row['chips']))
                                            <div class="pp-q__chips">
                                                @foreach ($row['chips'] as $chip)
                                                    <a href="{{ $chip['url'] }}" class="pp-chip">
                                                        {{ $chip['label'] }} <b>{{ $chip['count'] }}</b>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div class="pp-q__act">
                                        @foreach ($row['actions'] as $action)
                                            <a href="{{ $action['url'] }}"
                                               class="pp-btn pp-btn--sm{{ ($action['style'] ?? null) === 'pri' ? ' pp-btn--pri' : '' }}">
                                                {{ $action['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Money strip --}}
                <div class="pp-money" role="group" aria-label="Confirmed revenue and outstanding">
                    @foreach ($money as $cell)
                        <div>
                            <div class="pp-money__lab">{{ $cell['label'] }}</div>
                            <div class="pp-money__big{{ ! empty($cell['crit']) ? ' pp-money__big--crit' : '' }}">
                                {{ $cell['amount'] }}
                            </div>
                            <div class="pp-money__fine">
                                @if (! empty($cell['delta']))
                                    <span class="pp-delta pp-delta--{{ $cell['delta_dir'] }}">{{ $cell['delta'] }}</span>
                                    @if (! empty($cell['context']))
                                        <span>&nbsp;{{ $cell['context'] }}</span>
                                    @endif
                                @else
                                    {{ $cell['context'] }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Next match --}}
                @if ($next)
                    <div class="pp-panel">
                        <div class="pp-panel-head">
                            <h2>Next match</h2>
                            <div class="pp-spacer"></div>
                            <a href="{{ $next['edit_url'] }}" class="pp-btn pp-btn--sm pp-btn--ghost">Edit</a>
                        </div>
                        <div class="pp-nm">
                            <div class="pp-nm__when">
                                {{ $next['date'] }}@if ($next['venue']) &middot; {{ $next['venue'] }} @endif
                            </div>
                            <h3>{{ $next['title'] }}</h3>

                            @if ($next['max_entries'])
                                @php
                                    $pct = min(100, (int) round(($next['entries'] / max(1, $next['max_entries'])) * 100));
                                @endphp
                                <div class="pp-nm__meter" aria-label="Entries filled">
                                    <i style="width: {{ $pct }}%"></i>
                                </div>
                            @endif

                            <div class="pp-nm__facts">
                                <span>
                                    <b>{{ $next['entries'] }}</b>@if ($next['max_entries']) of <b>{{ $next['max_entries'] }}</b> @endif entered
                                </span>
                                @if ($next['new_this_week'] > 0)
                                    <span>&middot; <b>+{{ $next['new_this_week'] }}</b> new this week</span>
                                @endif
                                @if ($next['unpaid_entries'] > 0)
                                    <span>&middot; <b>{{ $next['unpaid_entries'] }}</b> unpaid</span>
                                @endif
                                @if ($next['entries_close_at'])
                                    @php
                                        $closesIn = (int) now()->diffInDays($next['entries_close_at'], false);
                                        $closesPill = $closesIn <= 2 ? 'pp-pill--wa' : 'pp-pill--in';
                                    @endphp
                                    <span class="pp-nm__pill">
                                        <span class="pp-pill {{ $closesPill }}">
                                            @if ($closesIn > 0)
                                                closes in {{ $closesIn }}d
                                            @elseif ($closesIn === 0)
                                                closes today
                                            @else
                                                entries closed
                                            @endif
                                        </span>
                                    </span>
                                @endif
                            </div>

                            <div class="pp-nm__acts">
                                <a href="{{ $next['squad_url'] }}" class="pp-btn pp-btn--sm pp-btn--pri">Squadding</a>
                                @if ($next['match_book_url'])
                                    <a href="{{ $next['match_book_url'] }}" class="pp-btn pp-btn--sm" target="_blank" rel="noopener">Match book</a>
                                @endif
                                <a href="{{ $next['report_url'] }}" class="pp-btn pp-btn--sm">Entry list</a>
                                <a href="{{ $next['edit_url'] }}" class="pp-btn pp-btn--sm pp-btn--ghost">Export</a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- RIGHT COLUMN: activity feed --}}
            <div style="display:flex;flex-direction:column;gap:1rem;min-width:0">
                <div class="pp-panel">
                    <div class="pp-panel-head">
                        <h2>Activity</h2>
                        <div class="pp-spacer"></div>
                    </div>

                    @php
                        $groups = ['today' => 'Today', 'this_week' => 'This week', 'earlier' => 'Earlier'];
                        $anyActivity = collect($activity)->flatten(1)->isNotEmpty();
                    @endphp

                    @if (! $anyActivity)
                        <div style="padding:1.5rem 1rem;text-align:center;color:var(--ui-ink-3);font-size:0.8125rem">
                            No recent activity. Signups, payments and match events will land here as they happen.
                        </div>
                    @else
                        @foreach ($groups as $key => $groupLabel)
                            @if (! empty($activity[$key]))
                                <div class="pp-act-day">{{ $groupLabel }}</div>
                                @foreach ($activity[$key] as $item)
                                    <div class="pp-act">
                                        <span class="pp-act__dot pp-act__dot--{{ $item['bucket'] ?? 'mem' }}" aria-hidden="true"></span>
                                        <div class="pp-act__t">
                                            @if (! empty($item['url']))
                                                <a href="{{ $item['url'] }}">{{ $item['description'] }}</a>
                                            @else
                                                {{ $item['description'] }}
                                            @endif
                                        </div>
                                        <div class="pp-act__time">
                                            {{ \Illuminate\Support\Carbon::parse($item['timestamp'])->diffForHumans(['short' => true]) }}
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        @endforeach
                    @endif

                    <div class="pp-legend" aria-label="Activity legend">
                        <span><i class="pp-legend__dot" style="background:var(--ui-good)"></i>Money</span>
                        <span><i class="pp-legend__dot" style="background:var(--ui-accent)"></i>Members</span>
                        <span><i class="pp-legend__dot" style="background:var(--ui-warn)"></i>Matches</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
