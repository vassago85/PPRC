<x-site.layout title="Membership cancelled">
    <x-site.section padding="default">
        <div class="mx-auto max-w-xl">
            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 text-center sm:p-10">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-200">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ $alreadyResigned ? 'You\'re already cancelled' : 'You\'re all set, ' . $member->first_name }}
                </h1>
                <p class="mt-3 text-slate-300">
                    @if ($alreadyResigned)
                        Your membership was already marked as resigned. No further action is needed.
                    @else
                        Your PPRC membership has been cancelled. We've sent a confirmation to your email and let the membership secretary know.
                    @endif
                </p>
                <p class="mt-6 text-sm text-slate-400">
                    Thanks for being part of the club. If you ever want to come back, drop a line to
                    <a href="mailto:membership@pretoriaprc.co.za" class="text-brand-300 hover:text-brand-200">membership@pretoriaprc.co.za</a>.
                </p>

                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-white/10">
                        Back to home
                    </a>
                </div>
            </div>
        </div>
    </x-site.section>
</x-site.layout>
