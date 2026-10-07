<x-portal.layout title="Pick your primary email">
    <div class="max-w-xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Pick your primary email</h1>
            <p class="mt-2 text-sm text-slate-400">
                Your account has more than one email address on it — this happens when the committee merges two records that belonged to you. You can sign in and reset your password with any of them, but only one is used for club mail (payment requests, confirmations, newsletters). Pick which one.
            </p>
        </div>

        <form method="post" action="{{ route('portal.account.primary-email.update') }}" class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 space-y-5">
            @csrf
            @method('put')

            <fieldset class="space-y-3">
                <legend class="text-sm text-slate-400 mb-2">Use this address for club mail</legend>
                @foreach ($addresses as $address)
                    <label class="flex items-center gap-3 rounded-lg border border-white/10 bg-white/5 px-4 py-3 text-sm text-white cursor-pointer transition hover:bg-white/10">
                        <input type="radio" name="primary_email" value="{{ $address }}"
                               @checked($loop->first ? old('primary_email', $user->email) === $address : old('primary_email') === $address)
                               class="h-4 w-4 border-white/20 bg-transparent text-sky-500 focus:ring-sky-500">
                        <span class="font-mono">{{ $address }}</span>
                        @if ($address === $user->email)
                            <span class="ml-auto rounded-full bg-sky-500/20 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-sky-200">Current</span>
                        @endif
                    </label>
                @endforeach
                @error('primary_email')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200">
                    Save
                </button>
                <a href="{{ route('portal.dashboard') }}" class="text-sm text-slate-400 transition hover:text-white">Decide later</a>
            </div>

            <p class="text-xs text-slate-500">
                Either address will keep working for sign-in and password reset — this choice only controls which one receives club mail.
            </p>
        </form>
    </div>
</x-portal.layout>
