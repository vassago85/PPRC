<x-site.auth-layout
    title="Verify email"
    eyebrow="Email verification"
    heading="Enter your verification code."
    subheading="We sent a numeric code to your email. It expires after a while for security — you can request a new one if needed."
>
    @if (session('status') === 'verification-pin-sent')
        <div class="rounded-md border border-emerald-400/30 bg-emerald-400/5 px-4 py-3 text-sm text-emerald-300">
            A new code has been sent to your email.
        </div>
    @endif

    @if (session('status') === 'verification-pin-wait')
        <div class="rounded-md border border-amber-400/30 bg-amber-400/5 px-4 py-3 text-sm text-amber-200">
            Please wait a couple of minutes before requesting another code automatically. You can still use a code from a recent email, or resend below.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.pin.verify') }}" class="mt-6 space-y-5">
        @csrf
        <x-site.input
            name="pin"
            label="Verification code"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            pattern="[0-9]*"
            maxlength="10"
            required
            autofocus
        />
        <x-site.button type="submit" size="lg" fullWidth>Verify email</x-site.button>
    </form>

    <div class="flex flex-col sm:flex-row gap-3 mt-8">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-site.button type="submit" variant="secondary">Email me a new code</x-site.button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-site.button type="submit" variant="secondary">Sign out</x-site.button>
        </form>
    </div>
</x-site.auth-layout>
