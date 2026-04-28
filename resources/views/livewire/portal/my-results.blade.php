<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">My Results</h1>
        <p class="mt-1 text-sm text-slate-400">Your shooting results across all events.</p>
    </div>

    @if ($this->results->isEmpty())
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-10 text-center">
            <p class="text-sm text-slate-400">No results yet. Once you shoot, your scores will appear here.</p>
        </div>
    @else
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] overflow-hidden">
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-white/10 text-left text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Event</th>
                            <th class="px-6 py-3 font-medium">Date</th>
                            <th class="px-6 py-3 font-medium">Format</th>
                            <th class="px-6 py-3 font-medium text-right">Rank</th>
                            <th class="px-6 py-3 font-medium text-right">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($this->results as $result)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-6 py-4">
                                    <a href="{{ url('/matches/' . $result->event->slug) }}#results" class="font-medium text-white hover:text-slate-300">
                                        {{ $result->event->title }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-slate-400">{{ $result->event->start_date->format('d M Y') }}</td>
                                <td class="px-6 py-4 text-slate-400">{{ $result->event->matchFormat?->short_name ?? '—' }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if ($result->dq)
                                        <span class="rounded-full bg-red-500/20 px-2 py-0.5 text-xs font-semibold text-red-400">DQ</span>
                                    @elseif ($result->dnf)
                                        <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-xs font-semibold text-amber-400">DNF</span>
                                    @elseif ($result->rank && $result->rank <= 3)
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-500/20 text-xs font-bold text-amber-400">{{ $result->rank }}</span>
                                    @else
                                        <span class="text-slate-400">{{ $result->rank ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-white">{{ $result->displayScore() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sm:hidden divide-y divide-white/5">
                @foreach ($this->results as $result)
                    <div class="px-5 py-4 space-y-1">
                        <a href="{{ url('/matches/' . $result->event->slug) }}#results" class="block font-medium text-white">{{ $result->event->title }}</a>
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span>{{ $result->event->start_date->format('d M Y') }}</span>
                            <span class="font-mono text-sm text-white">{{ $result->displayScore() }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
