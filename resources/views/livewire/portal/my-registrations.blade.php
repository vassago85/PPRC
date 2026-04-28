@php
    $badgeClasses = [
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-200',
        'info'    => 'bg-blue-50 text-blue-700 border-blue-200',
        'danger'  => 'bg-red-50 text-red-700 border-red-200',
        'gray'    => 'bg-slate-50 text-slate-700 border-slate-200',
    ];
@endphp

<div class="max-w-4xl mx-auto px-4 py-8 space-y-8">
    <header>
        <h1 class="text-2xl font-semibold text-slate-900">My Registrations</h1>
        <p class="text-sm text-slate-600">Your event registrations — upcoming and past.</p>
    </header>

    {{-- Upcoming --}}
    <section class="space-y-3">
        <h2 class="text-lg font-medium text-slate-900">Upcoming</h2>

        @if ($this->upcoming->isEmpty())
            <div class="rounded-lg border border-slate-200 bg-white p-6 text-center">
                <p class="text-sm text-slate-500">You have no upcoming event registrations.</p>
            </div>
        @else
            <div class="rounded-lg border border-slate-200 bg-white">
                {{-- Desktop table --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-slate-500 border-b border-slate-100">
                            <tr>
                                <th class="font-normal px-6 py-3">Event</th>
                                <th class="font-normal px-6 py-3">Date</th>
                                <th class="font-normal px-6 py-3">Location</th>
                                <th class="font-normal px-6 py-3">Division</th>
                                <th class="font-normal px-6 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($this->upcoming as $reg)
                                <tr>
                                    <td class="px-6 py-3">
                                        <a href="{{ url('/matches/' . $reg->event->slug) }}" class="font-medium text-slate-900 hover:text-blue-600">
                                            {{ $reg->event->title }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3 text-slate-700">{{ $reg->event->start_date->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-slate-700">{{ $reg->event->location_name ?? '—' }}</td>
                                    <td class="px-6 py-3 text-slate-700">{{ $reg->division ?? '—' }}</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium {{ $badgeClasses[$reg->status->color()] ?? $badgeClasses['gray'] }}">
                                            {{ $reg->status->label() }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="sm:hidden divide-y divide-slate-100">
                    @foreach ($this->upcoming as $reg)
                        <div class="px-4 py-4 space-y-1">
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ url('/matches/' . $reg->event->slug) }}" class="font-medium text-slate-900 hover:text-blue-600">
                                    {{ $reg->event->title }}
                                </a>
                                <span class="inline-flex shrink-0 rounded-full border px-2 py-0.5 text-xs font-medium {{ $badgeClasses[$reg->status->color()] ?? $badgeClasses['gray'] }}">
                                    {{ $reg->status->label() }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-slate-500">
                                <span>{{ $reg->event->start_date->format('d M Y') }}</span>
                                @if ($reg->event->location_name)
                                    <span>&middot; {{ $reg->event->location_name }}</span>
                                @endif
                                @if ($reg->division)
                                    <span>&middot; {{ $reg->division }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    {{-- Past --}}
    <section class="space-y-3">
        <h2 class="text-lg font-medium text-slate-900">Past</h2>

        @if ($this->past->isEmpty())
            <div class="rounded-lg border border-slate-200 bg-white p-6 text-center">
                <p class="text-sm text-slate-500">No past event registrations found.</p>
            </div>
        @else
            <div class="rounded-lg border border-slate-200 bg-white">
                {{-- Desktop table --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-slate-500 border-b border-slate-100">
                            <tr>
                                <th class="font-normal px-6 py-3">Event</th>
                                <th class="font-normal px-6 py-3">Date</th>
                                <th class="font-normal px-6 py-3">Location</th>
                                <th class="font-normal px-6 py-3">Division</th>
                                <th class="font-normal px-6 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($this->past as $reg)
                                <tr>
                                    <td class="px-6 py-3">
                                        <a href="{{ url('/matches/' . $reg->event->slug) }}" class="font-medium text-slate-900 hover:text-blue-600">
                                            {{ $reg->event->title }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3 text-slate-700">{{ $reg->event->start_date->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-slate-700">{{ $reg->event->location_name ?? '—' }}</td>
                                    <td class="px-6 py-3 text-slate-700">{{ $reg->division ?? '—' }}</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium {{ $badgeClasses[$reg->status->color()] ?? $badgeClasses['gray'] }}">
                                            {{ $reg->status->label() }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="sm:hidden divide-y divide-slate-100">
                    @foreach ($this->past as $reg)
                        <div class="px-4 py-4 space-y-1">
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ url('/matches/' . $reg->event->slug) }}" class="font-medium text-slate-900 hover:text-blue-600">
                                    {{ $reg->event->title }}
                                </a>
                                <span class="inline-flex shrink-0 rounded-full border px-2 py-0.5 text-xs font-medium {{ $badgeClasses[$reg->status->color()] ?? $badgeClasses['gray'] }}">
                                    {{ $reg->status->label() }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-slate-500">
                                <span>{{ $reg->event->start_date->format('d M Y') }}</span>
                                @if ($reg->event->location_name)
                                    <span>&middot; {{ $reg->event->location_name }}</span>
                                @endif
                                @if ($reg->division)
                                    <span>&middot; {{ $reg->division }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</div>
