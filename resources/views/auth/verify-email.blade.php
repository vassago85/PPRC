<x-site.auth-layout
    title="Verify email"
    eyebrow="Email verification"
    heading="Verify your email."
    subheading="Thanks for signing up. We've sent you a verification link — check your inbox to activate your account."
>
    @if (session('status') == 'verification-link-sent')
        <div class="rounded-md border border-emerald-400/30 bg-emerald-400/5 px-4 py-3 text-sm text-emerald-300">
            A new verification link has been sent to your email.
        </div>
    @endif

    <div class="flex flex-col sm:flex-row gap-3 mt-6">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-site.button type="submit">Resend verification email</x-site.button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-site.button type="submit" variant="secondary">Sign out</x-site.button>
        </form>
    </div>
</x-site.auth-layout>
