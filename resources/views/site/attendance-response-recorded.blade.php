<x-site.layout title="Thanks — response recorded">
    <x-site.section padding="default">
        <div class="mx-auto max-w-xl">
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 text-center sm:p-10">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full
                    @if ($response === App\Enums\AttendanceResponse::StillShooting) bg-emerald-500/15 text-emerald-200
                    @elseif ($response === App\Enums\AttendanceResponse::Unsure) bg-amber-500/15 text-amber-200
                    @else bg-rose-500/15 text-rose-200 @endif">
                    @if ($response === App\Enums\AttendanceResponse::StillShooting)
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    @elseif ($response === App\Enums\AttendanceResponse::Unsure)
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/>
                        </svg>
                    @else
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    @endif
                </div>

                <h1 class="text-2xl font-semibold tracking-tight">
                    @switch($response)
                        @case(App\Enums\AttendanceResponse::StillShooting)
                            Thanks — you're on the squad list.
                            @break
                        @case(App\Enums\AttendanceResponse::Unsure)
                            Thanks — we've noted you're unsure.
                            @break
                        @case(App\Enums\AttendanceResponse::Withdrawn)
                            Your entry has been withdrawn.
                            @break
                    @endswitch
                </h1>

                <p class="mt-3 text-slate-300">
                    @if ($response === App\Enums\AttendanceResponse::StillShooting)
                        We've recorded that you'll be shooting <strong>{{ $event?->title }}</strong>. See you on the day.
                    @elseif ($response === App\Enums\AttendanceResponse::Unsure)
                        We've flagged your entry as "unsure" for <strong>{{ $event?->title }}</strong>. If you can give us a firm answer closer to the day it helps with the squad list.
                    @else
                        You've been removed from <strong>{{ $event?->title }}</strong>. If a fee was paid, the treasurer will be in touch about a refund or credit.
                    @endif
                </p>

                @if ($alternates->isNotEmpty())
                    <p class="mt-6 text-xs uppercase tracking-wider text-slate-400">
                        Change your mind?
                    </p>
                    <div class="mt-3 flex flex-col items-center justify-center gap-2 sm:flex-row">
                        @foreach ($alternates as $alt)
                            <a href="{{ $alt['url'] }}"
                               class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-white/10">
                                {{ $alt['response']->label() }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ url('/matches') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-brand-400">
                        Browse other matches
                    </a>
                </div>
            </div>
        </div>
    </x-site.section>
</x-site.layout>
