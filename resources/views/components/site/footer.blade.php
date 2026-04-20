@php
    use App\Models\SiteSetting;
    $email = SiteSetting::get('contact.email');
    $phone = SiteSetting::get('contact.phone');
    $facebook = SiteSetting::get('contact.social.facebook');
    $instagram = SiteSetting::get('contact.social.instagram');

    $nav = [
        ['label' => 'Home',       'href' => url('/')],
        ['label' => 'About',      'href' => url('/about')],
        ['label' => 'Membership', 'href' => url('/membership')],
        ['label' => 'Matches',    'href' => url('/matches')],
        ['label' => 'Results',    'href' => url('/results')],
        ['label' => 'Shop',       'href' => url('/shop')],
        ['label' => 'Contact',    'href' => url('/contact')],
    ];
@endphp
<footer class="border-t border-white/10 bg-slate-950 text-slate-400">
    <x-site.container>
        <div class="py-14 grid grid-cols-1 md:grid-cols-3 gap-10">
            {{-- Brand --}}
            <div class="md:col-span-1">
                <a href="{{ url('/') }}" class="flex items-center gap-3 text-white">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-white text-slate-950 text-[10px] font-bold tracking-tighter">PPRC</span>
                    <span class="font-semibold tracking-tight">Pretoria Precision Rifle Club</span>
                </a>
                <p class="mt-4 text-sm max-w-xs">
                    Based in Pretoria, Gauteng. Started in 2023 by precision rifle shooters, for precision rifle shooters.
                </p>
            </div>

            {{-- Navigation --}}
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Navigation</p>
                <ul class="mt-4 grid grid-cols-2 gap-2 text-sm">
                    @foreach ($nav as $item)
                        <li>
                            <a href="{{ $item['href'] }}" class="text-slate-400 hover:text-white transition">
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Contact --}}
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Contact</p>
                <ul class="mt-4 space-y-2 text-sm">
                    @if ($email)
                        <li><a href="mailto:{{ $email }}" class="hover:text-white transition">{{ $email }}</a></li>
                    @endif
                    @if ($phone)
                        <li><a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="hover:text-white transition">{{ $phone }}</a></li>
                    @endif
                </ul>
                @if ($facebook || $instagram)
                    <div class="mt-4 flex gap-4 text-sm">
                        @if ($facebook)<a href="{{ $facebook }}" class="hover:text-white transition">Facebook</a>@endif
                        @if ($instagram)<a href="{{ $instagram }}" class="hover:text-white transition">Instagram</a>@endif
                    </div>
                @endif
            </div>
        </div>

        <div class="border-t border-white/10 py-6 flex flex-col md:flex-row items-center justify-between gap-2 text-xs text-slate-500">
            <p>© {{ now()->year }} Pretoria Precision Rifle Club.</p>
            <p>Built by <a href="https://charsleydigital.co.za" class="hover:text-slate-300 transition">Charsley Digital</a>.</p>
        </div>
    </x-site.container>
</footer>
