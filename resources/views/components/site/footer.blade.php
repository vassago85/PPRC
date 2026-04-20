@php
    use App\Models\SiteSetting;
    $email = SiteSetting::get('contact.email');
    $phone = SiteSetting::get('contact.phone');
    $address = SiteSetting::get('contact.physical_address');
    $facebook = SiteSetting::get('contact.social.facebook');
    $instagram = SiteSetting::get('contact.social.instagram');
@endphp
<footer class="mt-24 border-t border-slate-200 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
        <div class="md:col-span-2">
            <p class="font-semibold tracking-tight">Pretoria Precision Rifle Club</p>
            <p class="mt-2 text-sm text-slate-600 max-w-md">Precision rifle matches, training and community in Gauteng. SAPRF-affiliated, member-run, range-safe.</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Get in touch</p>
            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                @if ($email) <li><a href="mailto:{{ $email }}" class="hover:text-slate-900">{{ $email }}</a></li> @endif
                @if ($phone) <li>{{ $phone }}</li> @endif
                @if ($address) <li>{{ $address }}</li> @endif
            </ul>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Club</p>
            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                <li><a href="{{ url('/about') }}" class="hover:text-slate-900">About</a></li>
                <li><a href="{{ url('/membership') }}" class="hover:text-slate-900">Membership</a></li>
                <li><a href="{{ url('/exco') }}" class="hover:text-slate-900">Committee</a></li>
                <li><a href="{{ url('/faqs') }}" class="hover:text-slate-900">FAQs</a></li>
                <li><a href="{{ url('/contact') }}" class="hover:text-slate-900">Contact</a></li>
            </ul>
            @if ($facebook || $instagram)
                <div class="mt-4 flex gap-3 text-slate-500">
                    @if ($facebook)<a href="{{ $facebook }}" class="hover:text-slate-900">Facebook</a>@endif
                    @if ($instagram)<a href="{{ $instagram }}" class="hover:text-slate-900">Instagram</a>@endif
                </div>
            @endif
        </div>
    </div>
    <div class="border-t border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4 flex flex-col md:flex-row items-center justify-between text-xs text-slate-500">
            <p>© {{ now()->year }} Pretoria Precision Rifle Club. All rights reserved.</p>
            <p>Built by <a href="https://charsleydigital.co.za" class="hover:text-slate-700">Charsley Digital</a>.</p>
        </div>
    </div>
</footer>
