<div class="max-w-4xl mx-auto px-4 py-8 space-y-8">

    {{-- Greeting --}}
    <header>
        <h1 class="text-2xl font-semibold text-slate-900">
            Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }},
            {{ $this->member?->first_name ?? auth()->user()->name }}
        </h1>
        <p class="text-sm text-slate-600">Welcome to the PPRC member portal.</p>
    </header>

    {{-- Membership status --}}
    <section class="rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="text-lg font-medium text-slate-900">Membership</h2>

        @if ($this->membership)
            @php($m = $this->membership)
            <dl class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <dt class="text-slate-500">Type</dt>
                    <dd class="font-medium text-slate-900">{{ $m->membership_type_name_snapshot }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Status</dt>
                    <dd>
                        @php($color = match($m->status->color()) {
                            'success' => 'bg-emerald-100 text-emerald-800',
                            'warning' => 'bg-amber-100 text-amber-800',
                            'info'    => 'bg-sky-100 text-sky-800',
                            'danger'  => 'bg-red-100 text-red-800',
                            default   => 'bg-slate-100 text-slate-800',
                        })
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $color }}">
                            {{ $m->status->label() }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Expires</dt>
                    <dd class="text-slate-900">{{ $m->period_end?->format('d M Y') ?? '—' }}</dd>
                </div>
            </dl>

            @if ($this->needsRenewal)
                <div class="mt-5 flex items-center gap-3 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <svg class="h-5 w-5 shrink-0 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    <span>
                        @if ($m->status === App\Enums\MembershipStatus::Expired || $m->status === App\Enums\MembershipStatus::Cancelled)
                            Your membership has {{ $m->status->label() === 'Cancelled' ? 'been cancelled' : 'expired' }}.
                        @else
                            Your membership expires on {{ $m->period_end?->format('d M Y') }}.
                        @endif
                        <a href="{{ route('portal.membership') }}" class="font-medium underline hover:text-amber-700">Renew now</a>
                    </span>
                </div>
            @endif
        @else
            <p class="mt-4 text-sm text-slate-600">
                You don't have an active membership.
                <a href="{{ route('portal.membership') }}" class="font-medium text-slate-900 underline hover:text-slate-700">Set one up</a>
            </p>
        @endif
    </section>

    {{-- Upcoming matches --}}
    <section class="rounded-lg border border-slate-200 bg-white p-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-medium text-slate-900">Upcoming matches</h2>
            <a href="{{ route('matches') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">View all &rarr;</a>
        </div>

        @if ($this->upcomingMatches->isEmpty())
            <p class="mt-4 text-sm text-slate-500">No upcoming matches scheduled.</p>
        @else
            <ul class="mt-4 divide-y divide-slate-100">
                @foreach ($this->upcomingMatches as $event)
                    <li>
                        <a href="{{ route('matches.show', $event) }}" class="flex items-start gap-4 py-3 group">
                            <div class="shrink-0 w-12 text-center">
                                <span class="block text-xs font-medium uppercase text-slate-500">{{ $event->start_date->format('M') }}</span>
                                <span class="block text-lg font-semibold text-slate-900 leading-tight">{{ $event->start_date->format('d') }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-slate-900 group-hover:text-slate-600 truncate">{{ $event->title }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    @if ($event->matchFormat)
                                        <span>{{ $event->matchFormat->short_name }}</span>
                                        <span class="mx-1">&middot;</span>
                                    @endif
                                    @if ($event->location_name)
                                        <span>{{ $event->location_name }}</span>
                                    @endif
                                </p>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Recent results --}}
    <section class="rounded-lg border border-slate-200 bg-white p-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-medium text-slate-900">Recent results</h2>
            <a href="{{ route('portal.results') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">View all &rarr;</a>
        </div>

        @if ($this->recentResults->isEmpty())
            <p class="mt-4 text-sm text-slate-500">No results recorded yet.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-slate-500">
                        <tr>
                            <th class="font-normal pb-2">Match</th>
                            <th class="font-normal pb-2">Date</th>
                            <th class="font-normal pb-2 text-center">Rank</th>
                            <th class="font-normal pb-2 text-right">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->recentResults as $result)
                            <tr class="border-t border-slate-100">
                                <td class="py-2 text-slate-900 max-w-[12rem] truncate">{{ $result->event?->title ?? '—' }}</td>
                                <td class="py-2 text-slate-600 whitespace-nowrap">{{ $result->event?->start_date?->format('d M Y') ?? '—' }}</td>
                                <td class="py-2 text-center">
                                    @if ($result->rank)
                                        <span class="inline-flex items-center justify-center rounded-full {{ $result->rank <= 3 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }} h-6 w-6 text-xs font-medium">
                                            {{ $result->rank }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-2 text-right font-mono text-slate-900">{{ $result->displayScore() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Quick links --}}
    <section>
        <h2 class="text-lg font-medium text-slate-900 mb-4">Quick links</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <a href="{{ route('portal.membership') }}" class="rounded-lg border border-slate-200 bg-white p-4 text-center hover:border-slate-300 hover:shadow-sm transition">
                <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-900">My Membership</span>
            </a>

            <a href="{{ route('portal.results') }}" class="rounded-lg border border-slate-200 bg-white p-4 text-center hover:border-slate-300 hover:shadow-sm transition">
                <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 2c-1.716 0-3.408.106-5.07.31C3.806 2.45 3 3.414 3 4.517V17.25a.75.75 0 001.075.676L10 15.082l5.925 2.844A.75.75 0 0017 17.25V4.517c0-1.103-.806-2.068-1.93-2.207A41.403 41.403 0 0010 2z" clip-rule="evenodd" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-900">My Results</span>
            </a>

            <a href="{{ route('portal.registrations') }}" class="rounded-lg border border-slate-200 bg-white p-4 text-center hover:border-slate-300 hover:shadow-sm transition">
                <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-900">Registrations</span>
            </a>

            <a href="{{ route('shop') }}" class="rounded-lg border border-slate-200 bg-white p-4 text-center hover:border-slate-300 hover:shadow-sm transition">
                <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 5v1H4.667a1.75 1.75 0 00-1.743 1.598l-.826 9.5A1.75 1.75 0 003.84 19H16.16a1.75 1.75 0 001.743-1.902l-.826-9.5A1.75 1.75 0 0015.333 6H14V5a4 4 0 00-8 0zm4-2.5A2.5 2.5 0 007.5 5v1h5V5A2.5 2.5 0 0010 2.5zM7.5 10a2.5 2.5 0 005 0V8.75a.75.75 0 011.5 0V10a4 4 0 01-8 0V8.75a.75.75 0 011.5 0V10z" clip-rule="evenodd" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-900">Club Shop</span>
            </a>

            <a href="{{ route('portal.profile.edit') }}" class="rounded-lg border border-slate-200 bg-white p-4 text-center hover:border-slate-300 hover:shadow-sm transition">
                <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.84 1.804A1 1 0 018.82 1h2.36a1 1 0 01.98.804l.331 1.652a6.993 6.993 0 011.929 1.115l1.598-.54a1 1 0 011.186.447l1.18 2.044a1 1 0 01-.205 1.251l-1.267 1.113a7.047 7.047 0 010 2.228l1.267 1.113a1 1 0 01.206 1.25l-1.18 2.045a1 1 0 01-1.187.447l-1.598-.54a6.993 6.993 0 01-1.929 1.115l-.33 1.652a1 1 0 01-.98.804H8.82a1 1 0 01-.98-.804l-.331-1.652a6.993 6.993 0 01-1.929-1.115l-1.598.54a1 1 0 01-1.186-.447l-1.18-2.044a1 1 0 01.205-1.251l1.267-1.114a7.05 7.05 0 010-2.227L1.821 7.773a1 1 0 01-.206-1.25l1.18-2.045a1 1 0 011.187-.447l1.598.54A6.992 6.992 0 017.51 3.456l.33-1.652zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-900">Profile</span>
            </a>
        </div>
    </section>
</div>
