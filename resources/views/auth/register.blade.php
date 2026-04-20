<x-site.auth-layout
    title="Join PPRC"
    eyebrow="Join PPRC"
    heading="Create your account."
    subheading="Register an account, pick a membership option, and a committee member will approve your application."
>
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
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

        <x-site.button type="submit" size="lg" fullWidth>Create account</x-site.button>
    </form>

    <x-slot:footer>
        Already have an account?
        <a href="{{ route('login') }}" class="text-white hover:underline">Sign in</a>
    </x-slot:footer>
</x-site.auth-layout>
