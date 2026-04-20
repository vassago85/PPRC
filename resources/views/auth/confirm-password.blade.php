<x-site.auth-layout
    title="Confirm password"
    eyebrow="Security"
    heading="Confirm your password."
    subheading="Please confirm your password before continuing."
>
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <x-site.input
            name="password"
            label="Password"
            type="password"
            required
            autofocus
            autocomplete="current-password"
        />

        <x-site.button type="submit" size="lg" fullWidth>Confirm</x-site.button>
    </form>
</x-site.auth-layout>
