@php
    use Illuminate\Support\Str;
@endphp
<x-site.layout :title="$run->title" :description="Str::limit(strip_tags($run->description ?? ''), 160)">
    <x-site.section padding="lg">
        <p class="text-sm uppercase tracking-wide text-slate-500"><a href="{{ route('shop') }}" class="hover:text-white">Shop</a> / {{ $run->title }}</p>
        <h1 class="mt-3 text-4xl sm:text-5xl font-semibold tracking-tight">{{ $run->title }}</h1>
        @if ($run->description)
            <div class="prose prose-invert mt-6 max-w-3xl text-slate-300">
                {!! $run->description !!}
            </div>
        @endif
        <div class="mt-6 flex flex-wrap gap-3">
            @if ($acceptingOrders)
                <x-site.button href="{{ route('portal.shop.run', $run) }}" variant="primary">
                    Order in member portal
                </x-site.button>
                <p class="w-full text-sm text-slate-400">You need a PPRC portal login and member profile to place an order.</p>
            @else
                <x-site.card padding="md" class="border-dashed">
                    <p class="text-slate-300">This run is not accepting orders right now.</p>
                    @if ($run->orders_open_at && $run->orders_open_at->isFuture())
                        <p class="mt-2 text-sm text-slate-500">Orders open {{ $run->orders_open_at->timezone(config('app.timezone'))->format('l j F Y, H:i') }}.</p>
                    @endif
                </x-site.card>
            @endif
            <x-site.button href="{{ route('shop') }}#waitlist" variant="secondary" size="sm">Waitlist</x-site.button>
        </div>
    </x-site.section>

    @if ($products->isEmpty())
        <x-site.section tone="muted" padding="default">
            <p class="text-slate-400">No products in this run yet.</p>
        </x-site.section>
    @else
        <x-site.section tone="muted" padding="default">
            <h2 class="text-xl font-semibold text-white">Products</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    <x-site.card padding="none" class="overflow-hidden">
                        @if ($product->imageUrl())
                            <div class="aspect-[4/3] bg-slate-900">
                                <img src="{{ $product->imageUrl() }}" alt="" class="h-full w-full object-cover">
                            </div>
                        @endif
                        <div class="p-5">
                            <h3 class="font-medium text-white">{{ $product->name }}</h3>
                            @if ($product->description)
                                <div class="prose prose-invert prose-sm mt-2 max-w-none text-slate-400">
                                    {!! $product->description !!}
                                </div>
                            @endif
                            <p class="mt-3 text-sm font-semibold text-white">
                                {{ $product->currency }} {{ number_format($product->price_cents / 100, 2) }}
                            </p>
                        </div>
                    </x-site.card>
                @endforeach
            </div>
        </x-site.section>
    @endif
</x-site.layout>
