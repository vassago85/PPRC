<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">Edit profile</h1>
        <p class="mt-1 text-sm text-slate-400">Keep your club details up to date.</p>
    </div>

    @if (session('flash'))
        <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            {{ session('flash') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">

        <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Name</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-sm text-slate-400">First name</label>
                    <input id="first_name" type="text" wire:model="first_name"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                    @error('first_name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="last_name" class="block text-sm text-slate-400">Last name</label>
                    <input id="last_name" type="text" wire:model="last_name"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                    @error('last_name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="known_as" class="block text-sm text-slate-400">Known as <span class="text-slate-600">(nickname)</span></label>
                    <input id="known_as" type="text" wire:model="known_as"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Phone</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="phone_country_code" class="block text-sm text-slate-400">Code</label>
                    <input id="phone_country_code" type="text" wire:model="phone_country_code" placeholder="+27"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                </div>
                <div class="sm:col-span-2">
                    <label for="phone_number" class="block text-sm text-slate-400">Number</label>
                    <input id="phone_number" type="text" wire:model="phone_number" placeholder="82 123 4567"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Address</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm text-slate-400">Address line 1</label>
                    <input type="text" wire:model="address_line1"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                    @error('address_line1') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm text-slate-400">Address line 2 <span class="text-slate-600">(optional)</span></label>
                    <input type="text" wire:model="address_line2"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                </div>
                <div>
                    <label class="block text-sm text-slate-400">City</label>
                    <input type="text" wire:model="city"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                    @error('city') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-slate-400">Province</label>
                    <input type="text" wire:model="province"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                </div>
                <div>
                    <label class="block text-sm text-slate-400">Postal code</label>
                    <input type="text" wire:model="postal_code"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                </div>
                <div>
                    <label class="block text-sm text-slate-400">Country</label>
                    <input type="text" wire:model="country"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Date of birth</h2>
                <input type="date" wire:model="date_of_birth"
                    class="block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                @error('date_of_birth') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Shooting disciplines</h2>
                <div class="grid grid-cols-2 gap-2">
                    @foreach (\App\Livewire\Portal\ProfileEdit::DISCIPLINES as $discipline)
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" wire:model="shooting_disciplines" value="{{ $discipline }}"
                                class="rounded border-white/20 bg-white/5 text-white focus:ring-white/20" />
                            <span class="text-slate-300">{{ $discipline }}</span>
                        </label>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Profile photo</h2>
            @php $member = auth()->user()->member; @endphp
            <div class="flex items-center gap-6">
                @if ($photo)
                    <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="h-16 w-16 rounded-full object-cover ring-2 ring-white/20" />
                @elseif ($member->profile_photo_path)
                    <img src="{{ \App\Support\MediaDisk::url($member->profile_photo_path) }}" alt="Photo" class="h-16 w-16 rounded-full object-cover ring-2 ring-white/20" />
                @else
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white/10 text-slate-500">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    </div>
                @endif
                <div>
                    <input type="file" wire:model="photo" accept="image/*"
                        class="text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-white/15" />
                    <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP. Max 4 MB.</p>
                    @error('photo') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Save profile</span>
                <span wire:loading wire:target="save" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>
            </button>
        </div>
    </form>
</div>
