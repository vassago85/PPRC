<x-site.auth-layout
    title="Forgot password"
    eyebrow="Password reset"
    heading="Reset your password."
    subheading="Enter the email you registered with and we'll send you a reset link."
>
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <x-site.input
            name="email"
            label="Email"
            type="email"
            required
            autofocus
            autocomplete="username"
        />

        <x-site.button type="submit" size="lg" fullWidth>Email password reset link</x-site.button>
    </form>

    <x-slot:footer>
        Remembered it?
        <a href="{{ route('login') }}" class="text-white hover:underline">Back to sign in</a>
    </x-slot:footer>
</x-site.auth-layout>
