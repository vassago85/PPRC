<x-site.auth-layout
    title="Sign in"
    eyebrow="Sign in"
    heading="Welcome back."
    subheading="Sign in to access your membership, matches and orders."
>
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-site.input
            name="email"
            label="Email"
            type="email"
            required
            autofocus
            autocomplete="username"
        />

        <x-site.input
            name="password"
            label="Password"
            type="password"
            required
            autocomplete="current-password"
        />

        <div class="flex items-center justify-between text-sm">
            <label class="inline-flex items-center gap-2 text-slate-400">
                <input type="checkbox" name="remember" class="rounded border-white/20 bg-white/5 text-white focus:ring-white/30">
                Remember me
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-slate-300 hover:text-white transition">
                    Forgot password?
                </a>
            @endif
        </div>

        <x-site.button type="submit" size="lg" fullWidth>Sign in</x-site.button>
    </form>

    <x-slot:footer>
        Don't have an account?
        <a href="{{ route('register') }}" class="text-white hover:underline">Join PPRC</a>
    </x-slot:footer>
</x-site.auth-layout>
