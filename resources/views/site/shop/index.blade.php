@php
    use Illuminate\Support\Str;
@endphp
<x-site.layout title="Shop" description="PPRC club apparel and merchandise. Join the waitlist to be emailed when order windows open.">
    <x-site.section padding="lg">
        <x-site.eyebrow>Shop</x-site.eyebrow>
        <h1 class="mt-3 text-4xl sm:text-5xl font-semibold tracking-tight">Club apparel</h1>
        <p class="mt-5 max-w-2xl text-slate-300">
            We run apparel in batches. Browse upcoming ranges when we publish previews, and get an email when orders open by joining the waitlist below.
        </p>
    </x-site.section>

    @if (session('flash'))
        <x-site.section tone="muted" padding="sm">
            <x-site.card padding="md" class="border-emerald-500/30 bg-emerald-500/5">
                <p class="text-emerald-100">{{ session('flash') }}</p>
            </x-site.card>
        </x-site.section>
    @endif

    @if ($runs->isEmpty())
        <x-site.section tone="muted" padding="default">
            <x-site.card padding="lg" class="text-center border-dashed">
                <p class="text-slate-300">No apparel runs are published yet.</p>
                <p class="mt-2 text-sm text-slate-500">Join the waitlist — we will email you when the next window opens.</p>
            </x-site.card>
        </x-site.section>
    @else
        <x-site.section tone="muted" padding="default">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <h2 class="text-xl font-semibold text-white">Published runs</h2>
            </div>
            <ul class="mt-6 space-y-4">
                @foreach ($runs as $run)
                    <li>
                        <x-site.card padding="lg" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm uppercase tracking-wide text-slate-500">{{ $run->status->label() }}</p>
                                <h3 class="mt-1 text-lg font-semibold text-white">{{ $run->title }}</h3>
                                @if ($run->orders_open_at || $run->orders_close_at)
                                    <p class="mt-1 text-sm text-slate-400">
                                        @if ($run->orders_open_at)
                                            Opens {{ $run->orders_open_at->timezone(config('app.timezone'))->format('D j M Y, H:i') }}
                                        @endif
                                        @if ($run->orders_close_at)
                                            · Closes {{ $run->orders_close_at->timezone(config('app.timezone'))->format('D j M Y, H:i') }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                            <x-site.button href="{{ route('shop.run', $run) }}" variant="secondary" size="sm">
                                View run
                            </x-site.button>
                        </x-site.card>
                    </li>
                @endforeach
            </ul>
        </x-site.section>

        @if ($featured && $featured->catalogVisibleToPublic() && $featured->activeProducts->isNotEmpty())
            <x-site.section padding="default">
                <h2 class="text-xl font-semibold text-white">Preview — {{ $featured->title }}</h2>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featured->activeProducts->take(6) as $product)
                        <x-site.card padding="none" class="overflow-hidden">
                            @if ($product->imageUrl())
                                <div class="aspect-[4/3] bg-slate-900">
                                    <img src="{{ $product->imageUrl() }}" alt="" class="h-full w-full object-cover">
                                </div>
                            @endif
                            <div class="p-5">
                                <h3 class="font-medium text-white">{{ $product->name }}</h3>
                                <p class="mt-2 text-sm text-slate-400">{{ Str::limit(strip_tags($product->description ?? ''), 100) }}</p>
                                <p class="mt-3 text-sm font-semibold text-white">
                                    {{ $product->currency }} {{ number_format($product->price_cents / 100, 2) }}
                                </p>
                            </div>
                        </x-site.card>
                    @endforeach
                </div>
                <p class="mt-4 text-sm text-slate-500">Prices and items may change before orders open. <a href="{{ route('shop.run', $featured) }}" class="text-white underline underline-offset-2">View full run</a>.</p>
            </x-site.section>
        @endif
    @endif

    <x-site.section padding="default" id="waitlist">
        <x-site.card padding="lg">
            <h2 class="text-xl font-semibold text-white">Shop waitlist</h2>
            <p class="mt-2 max-w-xl text-sm text-slate-400">
                Enter your email and we will send a confirmation link. After you confirm, we will notify you when the next apparel order window opens (we do not email all members by default).
            </p>
            <form method="post" action="{{ route('shop.waitlist.store') }}" class="mt-6 space-y-4 max-w-md">
                @csrf
                <div class="hidden" aria-hidden="true">
                    <label for="shop-waitlist-website">Website</label>
                    <input type="text" name="website" id="shop-waitlist-website" tabindex="-1" autocomplete="off">
                </div>
                <div>
                    <label for="shop-waitlist-email" class="block text-sm font-medium text-slate-300">Email</label>
                    <input type="email" name="email" id="shop-waitlist-email" value="{{ old('email') }}" required
                        class="mt-1 block w-full rounded-md border border-white/10 bg-white/5 px-3 py-2 text-white placeholder:text-slate-500 focus:border-white/30 focus:outline-none focus:ring-1 focus:ring-white/20">
                    @error('email')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="shop-waitlist-name" class="block text-sm font-medium text-slate-300">Name (optional)</label>
                    <input type="text" name="name" id="shop-waitlist-name" value="{{ old('name') }}"
                        class="mt-1 block w-full rounded-md border border-white/10 bg-white/5 px-3 py-2 text-white placeholder:text-slate-500 focus:border-white/30 focus:outline-none focus:ring-1 focus:ring-white/20">
                </div>
                <x-site.button type="submit" variant="primary">Join waitlist</x-site.button>
            </form>
        </x-site.card>
    </x-site.section>
</x-site.layout>
