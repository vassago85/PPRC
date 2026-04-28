<div class="max-w-4xl mx-auto px-4 py-8 space-y-8">
    <header>
        <h1 class="text-2xl font-semibold text-slate-900">My Results</h1>
        <p class="text-sm text-slate-600">Your shooting results across all events.</p>
    </header>

    @if ($this->results->isEmpty())
        <div class="rounded-lg border border-slate-200 bg-white p-6 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-900">No results yet</p>
            <p class="mt-1 text-sm text-slate-500">Once you participate in events, your results will appear here.</p>
        </div>
    @else
        <section class="rounded-lg border border-slate-200 bg-white">
            {{-- Desktop table --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="font-normal px-6 py-3">Event</th>
                            <th class="font-normal px-6 py-3">Date</th>
                            <th class="font-normal px-6 py-3">Format</th>
                            <th class="font-normal px-6 py-3">Division</th>
                            <th class="font-normal px-6 py-3 text-right">Rank</th>
                            <th class="font-normal px-6 py-3 text-right">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($this->results as $result)
                            <tr>
                                <td class="px-6 py-3">
                                    <a href="{{ url('/matches/' . $result->event->slug) }}#results" class="font-medium text-slate-900 hover:text-blue-600">
                                        {{ $result->event->title }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-slate-700">{{ $result->event->start_date->format('d M Y') }}</td>
                                <td class="px-6 py-3 text-slate-700">{{ $result->event->matchFormat?->short_name ?? '—' }}</td>
                                <td class="px-6 py-3 text-slate-700">{{ $result->division ?? '—' }}</td>
                                <td class="px-6 py-3 text-right text-slate-900 font-medium">
                                    @if ($result->dq)
                                        <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">DQ</span>
                                    @elseif ($result->dnf)
                                        <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">DNF</span>
                                    @else
                                        {{ $result->rank ?? '—' }}
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right text-slate-900 tabular-nums">{{ $result->displayScore() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="sm:hidden divide-y divide-slate-100">
                @foreach ($this->results as $result)
                    <div class="px-4 py-4 space-y-1">
                        <a href="{{ url('/matches/' . $result->event->slug) }}#results" class="block font-medium text-slate-900 hover:text-blue-600">
                            {{ $result->event->title }}
                        </a>
                        <div class="flex items-center gap-3 text-xs text-slate-500">
                            <span>{{ $result->event->start_date->format('d M Y') }}</span>
                            @if ($result->event->matchFormat?->short_name)
                                <span>&middot; {{ $result->event->matchFormat->short_name }}</span>
                            @endif
                            @if ($result->division)
                                <span>&middot; {{ $result->division }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-slate-600">
                                Rank:
                                @if ($result->dq)
                                    <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">DQ</span>
                                @elseif ($result->dnf)
                                    <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">DNF</span>
                                @else
                                    <span class="font-medium text-slate-900">{{ $result->rank ?? '—' }}</span>
                                @endif
                            </span>
                            <span class="text-slate-600">Score: <span class="font-medium text-slate-900 tabular-nums">{{ $result->displayScore() }}</span></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
