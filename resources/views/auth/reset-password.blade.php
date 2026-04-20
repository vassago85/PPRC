<x-site.auth-layout
    title="Reset password"
    eyebrow="Password reset"
    heading="Choose a new password."
>
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-site.input
            name="email"
            label="Email"
            type="email"
            :value="$request->email"
            required
            autofocus
            autocomplete="username"
        />

        <x-site.input
            name="password"
            label="New password"
            type="password"
            required
            autocomplete="new-password"
        />

        <x-site.input
            name="password_confirmation"
            label="Confirm new password"
            type="password"
            required
            autocomplete="new-password"
        />

        <x-site.button type="submit" size="lg" fullWidth>Reset password</x-site.button>
    </form>
</x-site.auth-layout>
