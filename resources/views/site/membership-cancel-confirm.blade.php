<x-site.layout title="Cancel my membership">
    <x-site.section padding="default">
        <div class="mx-auto max-w-xl">
            @if ($alreadyResigned)
                <div class="rounded-2xl border border-emerald-400/25 bg-emerald-500/5 p-8 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight">Already cancelled</h1>
                    <p class="mt-3 text-slate-300">
                        Your PPRC membership is already marked as resigned. There's nothing more for you to do.
                    </p>
                    <p class="mt-4 text-sm text-slate-400">
                        Changed your mind? Email
                        <a href="mailto:membership@pretoriaprc.co.za" class="text-brand-300 hover:text-brand-200">membership@pretoriaprc.co.za</a>
                        and we'll reactivate your record.
                    </p>
                </div>
            @else
                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-8 sm:p-10">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-300">Cancel membership</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight">Are you sure, {{ $member->first_name }}?</h1>
                    <p class="mt-4 text-slate-300">
                        Cancelling your membership will mark you as resigned, end your access to member-only pricing and endorsement letters, and stop all future renewal reminders. Your match history stays on file.
                    </p>
                    <p class="mt-3 text-sm text-slate-400">
                        You can rejoin any time — just contact the membership secretary.
                    </p>

                    <form method="POST" action="{{ url()->current() }}" class="mt-8 space-y-5">
                        @csrf

                        <div>
                            <label class="text-xs font-medium uppercase tracking-wider text-slate-500">
                                Optional — let us know why you're leaving
                            </label>
                            <textarea name="reason" rows="3" maxlength="1000"
                                class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/80 px-4 py-3 text-sm text-white placeholder:text-slate-600 focus:border-brand-400/50 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                                placeholder="Moved cities, taking a break, prefer another club…"></textarea>
                            @error('reason')
                                <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <a href="{{ url('/') }}" class="text-sm font-medium text-slate-300 hover:text-white">
                                Never mind, take me home
                            </a>
                            <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-red-500">
                                Yes, cancel my membership
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </x-site.section>
</x-site.layout>
