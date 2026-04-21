{{--
    Public contact page. Renders a two-column layout:
      - Left: contact form (posts to /contact, delivered by ContactController)
      - Right: a calm "other ways to reach us" block (email + socials only —
        no phone per design decision).

    The "sent" success state is surfaced via flash key `contact.status`.
--}}
<x-site.layout
    title="Contact"
    description="Contact Pretoria Precision Rifle Club — match enquiries, membership queries, or general questions. We'll get back to you by email."
>
    <section class="relative isolate overflow-hidden bg-slate-950">
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute -top-40 left-1/2 h-[600px] w-[1100px] max-w-full -translate-x-1/2 rounded-full bg-brand-600/15 blur-[140px]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(29,138,192,0.12),transparent_60%)]"></div>
        </div>

        <x-site.container>
            <div class="py-14 sm:py-20 lg:py-24">
                <x-site.eyebrow>Contact</x-site.eyebrow>
                <h1 class="mt-3 max-w-3xl text-[2.15rem] font-semibold leading-[1.1] tracking-tight sm:text-5xl sm:leading-[1.05]">
                    Get in touch with the club.
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-300 sm:mt-5 sm:text-lg">
                    Match enquiries, membership questions, or just want to shoot with us?
                    Send us a message and a committee member will get back to you by email.
                </p>
            </div>
        </x-site.container>
    </section>

    <section class="relative border-t border-white/5 bg-slate-950 pb-14 sm:pb-20 lg:pb-24">
        <x-site.container>
            <div class="grid gap-8 pt-10 sm:gap-10 sm:pt-12 lg:grid-cols-12 lg:gap-16">
                {{-- =====================================================
                     CONTACT FORM
                ===================================================== --}}
                <div class="lg:col-span-7">
                    @if (session('contact.status') === 'sent')
                        <div
                            role="status"
                            class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 p-6 ring-1 ring-emerald-400/10"
                        >
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-emerald-400/30 bg-emerald-500/15">
                                    <svg class="size-4 text-emerald-200" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="font-semibold text-white">Message sent.</p>
                                    <p class="mt-1 text-sm text-emerald-100/80">
                                        Thanks &mdash; a committee member will be in touch by email shortly.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        @if (session('contact.status') === 'error')
                            <div
                                role="alert"
                                class="mb-6 rounded-2xl border border-rose-400/20 bg-rose-500/10 p-5 text-sm text-rose-100 ring-1 ring-rose-400/10"
                            >
                                We couldn't send your message just now. Please try again in a moment,
                                or email us directly at
                                <a href="mailto:{{ $email }}" class="font-semibold underline underline-offset-2 hover:text-white">{{ $email }}</a>.
                            </div>
                        @endif

                        <form
                            method="POST"
                            action="{{ route('contact.submit') }}"
                            class="rounded-2xl border border-white/10 bg-white/[0.02] p-5 ring-1 ring-white/5 sm:p-8"
                            novalidate
                        >
                            @csrf

                            {{-- Honeypot: off-screen, aria-hidden, not required, not labelled. --}}
                            <div class="absolute -left-[10000px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
                                <label for="website">Leave this field empty</label>
                                <input
                                    type="text"
                                    id="website"
                                    name="website"
                                    tabindex="-1"
                                    autocomplete="off"
                                    value="{{ old('website') }}"
                                />
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2 sm:gap-5">
                                <x-site.input
                                    name="name"
                                    label="Your name"
                                    required
                                    autocomplete="name"
                                    placeholder="Jane Shooter"
                                />
                                <x-site.input
                                    name="email"
                                    type="email"
                                    label="Email"
                                    required
                                    autocomplete="email"
                                    placeholder="you@example.com"
                                />
                            </div>

                            <div class="mt-5">
                                <x-site.input
                                    name="subject"
                                    label="Subject (optional)"
                                    placeholder="Membership, matches, something else…"
                                    :value="request('subject', '')"
                                />
                            </div>

                            <div class="mt-5">
                                <label for="message" class="block text-sm font-medium text-slate-300">
                                    Message
                                </label>
                                <textarea
                                    id="message"
                                    name="message"
                                    rows="6"
                                    required
                                    placeholder="Tell us how we can help."
                                    @class([
                                        'mt-2 block w-full rounded-md border border-white/10 bg-white/5 px-3.5 py-2.5 text-white placeholder:text-slate-500',
                                        'focus:border-white/30 focus:bg-white/[0.07] focus:outline-none',
                                        'border-rose-400/40' => $errors->has('message'),
                                    ])
                                >{{ old('message') }}</textarea>
                                @error('message')
                                    <p class="mt-1.5 text-xs text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-6 flex flex-col-reverse items-stretch justify-between gap-4 sm:mt-7 sm:flex-row sm:items-center">
                                <p class="text-xs text-slate-500">
                                    We only use this to reply to your message. No newsletters, no spam.
                                </p>
                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-6 py-3.5 font-semibold tracking-tight text-white shadow-lg shadow-brand-600/30 transition hover:bg-brand-400 sm:w-auto"
                                >
                                    Send message
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>

                {{-- =====================================================
                     OTHER WAYS TO REACH THE CLUB
                ===================================================== --}}
                <aside class="lg:col-span-5">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 ring-1 ring-white/5 sm:p-8">
                        <x-site.eyebrow>Reach us</x-site.eyebrow>
                        <h2 class="mt-3 text-xl font-semibold tracking-tight sm:text-2xl">Other ways to get hold of PPRC.</h2>

                        <dl class="mt-6 space-y-5 text-sm">
                            @if ($email)
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-brand-400/30 bg-brand-500/15">
                                        <svg class="size-4 text-brand-200" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Email</dt>
                                        <dd class="mt-0.5">
                                            <a href="mailto:{{ $email }}" class="text-white transition hover:text-brand-200">{{ $email }}</a>
                                        </dd>
                                    </div>
                                </div>
                            @endif

                            @if ($address)
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-brand-400/30 bg-brand-500/15">
                                        <svg class="size-4 text-brand-200" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 0 1 15 0Z" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Based in</dt>
                                        <dd class="mt-0.5 text-white">{{ $address }}</dd>
                                    </div>
                                </div>
                            @endif

                            @if ($facebook || $instagram || $whatsapp)
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-brand-400/30 bg-brand-500/15">
                                        <svg class="size-4 text-brand-200" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Social</dt>
                                        <dd class="mt-0.5 flex flex-wrap gap-x-4 gap-y-1">
                                            @if ($facebook)
                                                <a href="{{ $facebook }}" class="text-white transition hover:text-brand-200" target="_blank" rel="noopener">Facebook</a>
                                            @endif
                                            @if ($instagram)
                                                <a href="{{ $instagram }}" class="text-white transition hover:text-brand-200" target="_blank" rel="noopener">Instagram</a>
                                            @endif
                                            @if ($whatsapp)
                                                <a href="{{ $whatsapp }}" class="text-white transition hover:text-brand-200" target="_blank" rel="noopener">WhatsApp</a>
                                            @endif
                                        </dd>
                                    </div>
                                </div>
                            @endif
                        </dl>

                        <p class="mt-8 border-t border-white/10 pt-6 text-sm text-slate-400">
                            Prefer email directly? Send to
                            <a href="mailto:{{ $email }}" class="font-semibold text-white transition hover:text-brand-200">{{ $email }}</a>
                            &mdash; the contact form above delivers there too.
                        </p>
                    </div>
                </aside>
            </div>
        </x-site.container>
    </section>
</x-site.layout>
