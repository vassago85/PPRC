<div class="space-y-8">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500">
                <a href="{{ route('portal.documents') }}" wire:navigate class="hover:text-slate-300">&larr; Back to documents</a>
            </p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-white">Apply for an endorsement letter</h1>
            <p class="mt-1 text-sm text-slate-400">
                Used to support a new firearm licence application.
                Fields are used <strong>verbatim</strong> on your endorsement letter — please double-check spelling.
            </p>
        </div>
        <div class="hidden shrink-0 sm:block">
            <svg class="h-10 w-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
        </div>
    </div>

    @if (session('flash_error'))
        <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            {{ session('flash_error') }}
        </div>
    @endif

    @if (! $this->hasActiveMembership)
        <section class="rounded-2xl border border-amber-500/30 bg-amber-500/10 px-5 py-4 text-sm text-amber-300">
            You need an active membership to request an endorsement.
            <a href="{{ route('portal.membership') }}" wire:navigate class="font-semibold underline">Manage membership</a>.
        </section>
    @elseif ($this->hasPendingEndorsement)
        <section class="rounded-2xl border border-amber-500/30 bg-amber-500/10 px-5 py-4 text-sm text-amber-300">
            You already have a pending endorsement request under review. Please wait for the committee to action it before submitting another.
            <a href="{{ route('portal.documents') }}" wire:navigate class="font-semibold underline">Back to documents</a>.
        </section>
    @else
        <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <div class="space-y-5">
                {{-- Identity --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-400">RSA ID number</label>
                        <input type="text" wire:model="idNumber" maxlength="32" placeholder="e.g. 8501015800087"
                            class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                        @error('idNumber') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400">Purpose</label>
                        <input type="text" wire:model="reason" placeholder="Sport shooting"
                            class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                        @error('reason') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Item type toggle --}}
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Applying for</label>
                    <div class="flex gap-2">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" wire:model.live="itemType" value="rifle" class="peer sr-only" />
                            <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3 text-center text-sm text-slate-300 transition peer-checked:border-white/40 peer-checked:bg-white/10 peer-checked:text-white">
                                Complete Rifle
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" wire:model.live="itemType" value="component" class="peer sr-only" />
                            <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3 text-center text-sm text-slate-300 transition peer-checked:border-white/40 peer-checked:bg-white/10 peer-checked:text-white">
                                Component / Part
                            </div>
                        </label>
                    </div>
                    @error('itemType') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Conditional: rifle action type OR component type --}}
                @if ($itemType === 'rifle')
                    <div>
                        <label class="block text-sm text-slate-400">Action type</label>
                        <select wire:model="firearmType"
                            class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20">
                            <option value="Bolt action">Bolt action</option>
                            <option value="Semi-automatic">Semi-automatic</option>
                            <option value="Lever action">Lever action</option>
                            <option value="Pump action">Pump action</option>
                            <option value="Single shot">Single shot</option>
                            <option value="Other">Other</option>
                        </select>
                        @error('firearmType') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div>
                        <label class="block text-sm text-slate-400">Component</label>
                        <select wire:model="componentType"
                            class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20">
                            <option value="">Select component...</option>
                            <option value="Barrel">Barrel</option>
                            <option value="Action">Action / receiver</option>
                            <option value="Stock / Chassis">Stock / chassis</option>
                            <option value="Trigger">Trigger</option>
                            <option value="Bolt">Bolt</option>
                            <option value="Other">Other</option>
                        </select>
                        @error('componentType') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Make + calibre --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-400">Make / brand</label>
                        <input type="text" wire:model="make" placeholder="e.g. Eagle Barrels, Tikka, Bartlein"
                            class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                        @error('make') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400">Calibre</label>
                        <input type="text" wire:model="calibre" placeholder="e.g. 6mm Dasher, 6.5 Creedmoor, .308 Win"
                            class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                        @error('calibre') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <p class="text-sm text-slate-400">Serial numbers <span class="text-slate-600">(at least one is required — fill in both if you have them)</span></p>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase tracking-wider text-slate-500">Action serial</label>
                            <input type="text" wire:model="actionSerialNumber" placeholder="As stamped on the action / receiver"
                                class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                            @error('actionSerialNumber') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider text-slate-500">Barrel serial</label>
                            <input type="text" wire:model="barrelSerialNumber" placeholder="As stamped on the barrel"
                                class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                            @error('barrelSerialNumber') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-400">Additional details <span class="text-slate-600">(optional — model, notes)</span></label>
                    <input type="text" wire:model="firearmDetails" placeholder="e.g. Stiller TAC30 action, 26'' barrel"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-white/20 focus:outline-none focus:ring-1 focus:ring-white/20" />
                    @error('firearmDetails') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="button" wire:click="requestEndorsement" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:opacity-50">
                        <span wire:loading.remove wire:target="requestEndorsement">Submit request</span>
                        <span wire:loading wire:target="requestEndorsement" class="h-4 w-4 animate-spin rounded-full border-2 border-slate-950/30 border-t-slate-950"></span>
                    </button>
                    <a href="{{ route('portal.documents') }}" wire:navigate
                       class="text-sm text-slate-400 hover:text-slate-200">Cancel</a>
                </div>
            </div>
        </section>
    @endif
</div>
