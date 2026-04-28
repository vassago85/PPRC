@php
    $badgeClasses = [
        'success' => 'bg-emerald-500/20 text-emerald-400 ring-emerald-500/30',
        'warning' => 'bg-amber-500/20 text-amber-400 ring-amber-500/30',
        'info'    => 'bg-sky-500/20 text-sky-400 ring-sky-500/30',
        'danger'  => 'bg-red-500/20 text-red-400 ring-red-500/30',
        'gray'    => 'bg-slate-500/20 text-slate-400 ring-slate-500/30',
    ];
@endphp

<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">My Registrations</h1>
        <p class="mt-1 text-sm text-slate-400">Your event registrations — upcoming and past.</p>
    </div>

    @foreach (['Upcoming' => $this->upcoming, 'Past' => $this->past] as $label => $items)
        <section class="space-y-3">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</h2>

            @if ($items->isEmpty())
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 text-center">
                    <p class="text-sm text-slate-500">No {{ strtolower($label) }} registrations.</p>
                </div>
            @else
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] overflow-hidden">
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-white/10 text-left text-xs uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Event</th>
                                    <th class="px-6 py-3 font-medium">Date</th>
                                    <th class="px-6 py-3 font-medium">Location</th>
                                    <th class="px-6 py-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach ($items as $reg)
                                    <tr class="hover:bg-white/[0.02]">
                                        <td class="px-6 py-4">
                                            <a href="{{ url('/matches/' . $reg->event->slug) }}" class="font-medium text-white hover:text-slate-300">{{ $reg->event->title }}</a>
                                        </td>
                                        <td class="px-6 py-4 text-slate-400">{{ $reg->event->start_date->format('d M Y') }}</td>
                                        <td class="px-6 py-4 text-slate-400">{{ $reg->event->location_name ?? '—' }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badgeClasses[$reg->status->color()] ?? $badgeClasses['gray'] }}">
                                                {{ $reg->status->label() }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="sm:hidden divide-y divide-white/5">
                        @foreach ($items as $reg)
                            <div class="px-5 py-4">
                                <div class="flex items-start justify-between gap-2">
                                    <a href="{{ url('/matches/' . $reg->event->slug) }}" class="font-medium text-white">{{ $reg->event->title }}</a>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badgeClasses[$reg->status->color()] ?? $badgeClasses['gray'] }}">
                                        {{ $reg->status->label() }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $reg->event->start_date->format('d M Y') }}@if ($reg->event->location_name) · {{ $reg->event->location_name }}@endif</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @endforeach
</div>
