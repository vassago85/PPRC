<x-site.auth-layout
    title="Join PPRC"
    eyebrow="Join PPRC"
    heading="Create your member account."
    subheading="A PPRC membership is for shooters who want member pricing, household sub-members, and recorded results over the season. If you only want to shoot one upcoming match, you don't need to do this — see the panel below."
>
    {{--
        The purpose-picker intercepts people who really just want to enter a
        single match. They used to land straight on the form, create a user +
        pending Member row, and never come back — clogging the admin with
        orphaned half-signups. We default to "Join as a member" so legitimate
        joiners aren't blocked by an extra click.
    --}}
    <div
        x-data="{ purpose: 'join' }"
        class="space-y-6"
    >
        <fieldset class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
            <legend class="px-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Before you start</legend>
            <p class="text-sm text-slate-300">Why are you here?</p>

            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                <label
                    :class="purpose === 'join'
                        ? 'border-brand-400/60 bg-brand-500/10 text-white'
                        : 'border-white/10 bg-slate-950/40 text-slate-300 hover:border-white/20'"
                    class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm transition"
                >
                    <input
                        type="radio"
                        name="purpose"
                        value="join"
                        x-model="purpose"
                        class="mt-0.5 h-4 w-4 border-white/20 bg-slate-950 text-brand-500 focus:ring-brand-500/40"
                    />
                    <span>
                        <span class="block font-medium">Join PPRC as a member</span>
                        <span class="mt-0.5 block text-xs text-slate-400">Member pricing, household sub-members, season results.</span>
                    </span>
                </label>

                <label
                    :class="purpose === 'match'
                        ? 'border-emerald-400/60 bg-emerald-500/10 text-white'
                        : 'border-white/10 bg-slate-950/40 text-slate-300 hover:border-white/20'"
                    class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm transition"
                >
                    <input
                        type="radio"
                        name="purpose"
                        value="match"
                        x-model="purpose"
                        class="mt-0.5 h-4 w-4 border-white/20 bg-slate-950 text-emerald-500 focus:ring-emerald-500/40"
                    />
                    <span>
                        <span class="block font-medium">I just want to shoot a match</span>
                        <span class="mt-0.5 block text-xs text-slate-400">No PPRC account needed — enter as a guest.</span>
                    </span>
                </label>
            </div>
        </fieldset>

        {{-- "Shoot a match" branch: steer them to the match page and hide the
             membership form entirely so there's no accidental submit. --}}
        <div x-show="purpose === 'match'" x-cloak dusk="guest-match-panel">
            <div class="rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-5">
                <h2 class="text-base font-semibold text-white">You don't need an account to shoot a match.</h2>
                <p class="mt-2 text-sm text-emerald-100/90">
                    Guests are welcome at every PPRC match. Pick an upcoming match and enter with just your name, email and (if asked) your division. We email you a 6-digit code to confirm — that's it.
                </p>

                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ url('/matches') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-emerald-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        See upcoming matches
                    </a>
                    <button type="button" @click="purpose = 'join'"
                        class="text-sm text-slate-400 underline decoration-slate-700 underline-offset-2 hover:text-slate-200">
                        Actually, I want to join as a member
                    </button>
                </div>
            </div>
        </div>

        {{-- "Join" branch: the real Fortify form. --}}
        <form
            x-show="purpose === 'join'"
            method="POST"
            action="{{ route('register') }}"
            class="space-y-5"
            dusk="member-register-form"
        >
            @csrf

            <x-site.input
                name="name"
                label="Full name"
                required
                autofocus
                autocomplete="name"
            />

            <x-site.input
                name="email"
                label="Email"
                type="email"
                required
                autocomplete="username"
            />

            <x-site.input
                name="password"
                label="Password"
                type="password"
                required
                autocomplete="new-password"
            />

            <x-site.input
                name="password_confirmation"
                label="Confirm password"
                type="password"
                required
                autocomplete="new-password"
            />

            {{-- Honeypot: hidden from humans, bots fill it --}}
            <div class="absolute -left-[9999px] opacity-0" aria-hidden="true" tabindex="-1">
                <input type="text" name="website" value="" autocomplete="off" tabindex="-1" />
            </div>

            {{-- Cloudflare Turnstile --}}
            @if (config('services.turnstile.site_key'))
                <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="dark"></div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            @endif

            <x-site.button type="submit" size="lg" fullWidth>Create account</x-site.button>
        </form>
    </div>

    <x-slot:footer>
        Already have an account?
        <a href="{{ route('login') }}" class="text-white hover:underline">Sign in</a>
    </x-slot:footer>
</x-site.auth-layout>
