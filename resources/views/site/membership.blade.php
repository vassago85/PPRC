<x-site.layout
    title="Membership"
    description="Join Pretoria Precision Rifle Club — register online, pay by EFT or Paystack, and start shooting PRS and PR22 matches."
>
    <x-site.section padding="lg">
        <x-site.eyebrow>Membership</x-site.eyebrow>
        <h1 class="mt-3 text-4xl sm:text-5xl font-semibold tracking-tight">Join Pretoria Precision Rifle Club.</h1>
        <p class="mt-5 max-w-2xl text-lg text-slate-300">
            PPRC membership is managed online through the member portal. Register an account, choose a membership
            option, and a committee member will approve your application and issue your membership number.
        </p>
    </x-site.section>

    <x-site.section tone="muted" padding="default">
        <div class="grid gap-6 md:grid-cols-3">
            <x-site.card padding="lg">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-500/15 text-sm font-semibold text-brand-200 ring-1 ring-inset ring-brand-400/30">1</span>
                    <h3 class="font-semibold text-white">Register an account</h3>
                </div>
                <p class="mt-3 text-sm text-slate-400">
                    Create a login and fill in your shooter profile. Takes a couple of minutes.
                </p>
            </x-site.card>

            <x-site.card padding="lg">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-500/15 text-sm font-semibold text-brand-200 ring-1 ring-inset ring-brand-400/30">2</span>
                    <h3 class="font-semibold text-white">Pick a membership</h3>
                </div>
                <p class="mt-3 text-sm text-slate-400">
                    Full member, junior, or sub-member — tiers and pricing are visible in the portal once you're
                    signed in.
                </p>
            </x-site.card>

            <x-site.card padding="lg">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-500/15 text-sm font-semibold text-brand-200 ring-1 ring-inset ring-brand-400/30">3</span>
                    <h3 class="font-semibold text-white">Pay &amp; get approved</h3>
                </div>
                <p class="mt-3 text-sm text-slate-400">
                    Pay by bank EFT (with proof of payment uploaded) or by card via Paystack. A committee member
                    approves and issues your membership number.
                </p>
            </x-site.card>
        </div>

        <div class="mt-12 flex flex-col items-start gap-3 sm:flex-row sm:items-center">
            <x-site.button :href="url('/register')">Start my application</x-site.button>
            <x-site.button :href="url('/login')" variant="secondary">Sign in to the portal</x-site.button>
            <p class="text-sm text-slate-500 sm:ml-3">Already a member? Sign in to renew or update your details.</p>
        </div>
    </x-site.section>

    <x-site.section padding="default">
        <div class="grid gap-10 lg:grid-cols-2">
            <div class="space-y-4 text-slate-300">
                <h2 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">What you get</h2>
                <ul class="space-y-3 text-slate-300">
                    <li class="flex gap-3">
                        <span class="mt-1 block h-1.5 w-1.5 shrink-0 rounded-full bg-brand-400"></span>
                        <span>Entry to PPRC-hosted PRS (centerfire) and PR22 matches at member pricing.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1 block h-1.5 w-1.5 shrink-0 rounded-full bg-brand-400"></span>
                        <span>Results and rankings recorded against your profile over the season.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1 block h-1.5 w-1.5 shrink-0 rounded-full bg-brand-400"></span>
                        <span>Club comms and match notifications by email.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1 block h-1.5 w-1.5 shrink-0 rounded-full bg-brand-400"></span>
                        <span>Junior and sub-member options for family members shooting under an adult.</span>
                    </li>
                </ul>
            </div>
            <div class="space-y-4 text-slate-300">
                <h2 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">Got a question first?</h2>
                <p>
                    If you'd like to know more before applying — whether PRS is right for you, what gear you need,
                    or how matches are run — drop us a line.
                </p>
                <div class="pt-2">
                    <x-site.button :href="url('/contact')" variant="secondary">Contact the committee</x-site.button>
                </div>
            </div>
        </div>
    </x-site.section>
</x-site.layout>
