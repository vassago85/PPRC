<div class="max-w-4xl mx-auto px-4 py-8 space-y-8">
    <header>
        <h1 class="text-2xl font-semibold text-slate-900">Edit profile</h1>
        <p class="text-sm text-slate-600">Keep your club details up to date.</p>
    </header>

    @if (session('flash'))
        <div class="rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
            {{ session('flash') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-8">

        {{-- Name --}}
        <section class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
            <h2 class="text-lg font-medium text-slate-900">Name</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <label for="first_name" class="block font-medium text-slate-700">First name</label>
                    <input id="first_name" type="text" wire:model="first_name"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                    @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="last_name" class="block font-medium text-slate-700">Last name</label>
                    <input id="last_name" type="text" wire:model="last_name"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                    @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="known_as" class="block font-medium text-slate-700">Known as <span class="font-normal text-slate-500">(nickname)</span></label>
                    <input id="known_as" type="text" wire:model="known_as"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                    @error('known_as') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Phone --}}
        <section class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
            <h2 class="text-lg font-medium text-slate-900">Phone</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <label for="phone_country_code" class="block font-medium text-slate-700">Country code</label>
                    <input id="phone_country_code" type="text" wire:model="phone_country_code" placeholder="+27"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                    @error('phone_country_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="phone_number" class="block font-medium text-slate-700">Number</label>
                    <input id="phone_number" type="text" wire:model="phone_number" placeholder="82 123 4567"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                    @error('phone_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Address --}}
        <section class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
            <h2 class="text-lg font-medium text-slate-900">Address</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="sm:col-span-2">
                    <label for="address_line1" class="block font-medium text-slate-700">Address line 1</label>
                    <input id="address_line1" type="text" wire:model="address_line1"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                    @error('address_line1') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="address_line2" class="block font-medium text-slate-700">Address line 2 <span class="font-normal text-slate-500">(optional)</span></label>
                    <input id="address_line2" type="text" wire:model="address_line2"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                </div>
                <div>
                    <label for="city" class="block font-medium text-slate-700">City</label>
                    <input id="city" type="text" wire:model="city"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                    @error('city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="province" class="block font-medium text-slate-700">Province</label>
                    <input id="province" type="text" wire:model="province"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                    @error('province') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="postal_code" class="block font-medium text-slate-700">Postal code</label>
                    <input id="postal_code" type="text" wire:model="postal_code"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                    @error('postal_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="country" class="block font-medium text-slate-700">Country</label>
                    <input id="country" type="text" wire:model="country"
                        class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                    @error('country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Date of birth --}}
        <section class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
            <h2 class="text-lg font-medium text-slate-900">Date of birth</h2>
            <div class="text-sm">
                <input id="date_of_birth" type="date" wire:model="date_of_birth"
                    class="block w-full sm:w-56 rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                @error('date_of_birth') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>

        {{-- Shooting disciplines --}}
        <section class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
            <h2 class="text-lg font-medium text-slate-900">Shooting disciplines</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                @foreach (\App\Livewire\Portal\ProfileEdit::DISCIPLINES as $discipline)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="shooting_disciplines" value="{{ $discipline }}"
                            class="rounded border-slate-300 text-slate-900 focus:ring-slate-200" />
                        <span class="text-slate-700">{{ $discipline }}</span>
                    </label>
                @endforeach
            </div>
            @error('shooting_disciplines') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('shooting_disciplines.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- Profile photo --}}
        <section class="rounded-lg border border-slate-200 bg-white p-6 space-y-4">
            <h2 class="text-lg font-medium text-slate-900">Profile photo</h2>

            @php
                $member = auth()->user()->member;
            @endphp

            <div class="flex items-center gap-6">
                @if ($photo)
                    <img src="{{ $photo->temporaryUrl() }}" alt="Preview"
                        class="h-20 w-20 rounded-full object-cover ring-2 ring-slate-200" />
                @elseif ($member->profile_photo_path)
                    <img src="{{ Storage::disk('s3')->url($member->profile_photo_path) }}" alt="Current photo"
                        class="h-20 w-20 rounded-full object-cover ring-2 ring-slate-200" />
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                @endif

                <div class="text-sm">
                    <input type="file" wire:model="photo" accept="image/*"
                        class="text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200" />
                    <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP. Max 4 MB.</p>
                    @error('photo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Submit --}}
        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Save profile</span>
                <span wire:loading wire:target="save" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
            </button>
        </div>
    </form>
</div>
