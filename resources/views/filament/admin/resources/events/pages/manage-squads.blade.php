<x-filament-panels::page>
    @php
        /** @var \App\Models\Event $event */
        $event = $this->getRecord();
        $grouped = $this->getEntriesBySquad();
        $unassigned = $grouped[0] ?? collect();
        $squadKeys = collect(array_keys($grouped))
            ->filter(fn ($k) => $k > 0)
            ->sort()
            ->values();
        $totalEntries = collect($grouped)->reduce(fn ($carry, $coll) => $carry + $coll->count(), 0);
    @endphp

    {{-- Match-level summary so the admin can sanity-check stage info --}}
    {{-- without leaving the squadding board.                          --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-4 text-sm shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Match:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $event->title }}</span>
            </div>
            @if ($event->start_date)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Date:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $event->start_date->format('d M Y') }}</span>
                </div>
            @endif
            <div>
                <span class="text-gray-500 dark:text-gray-400">Entries:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $totalEntries }}</span>
            </div>
            @if ($event->stage_count)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Stages:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $event->stage_count }}</span>
                </div>
            @endif
            @if ($event->stage_time_seconds)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Time/stage:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $event->stage_time_seconds }}s</span>
                </div>
            @endif
        </div>
        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Drag shooters between squads to assign them. Changes save immediately. Firing order is reset when a shooter changes squad — set it in the Entries table after the layout settles.
        </p>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        <button
            type="button"
            wire:click="addSquad"
            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add squad
        </button>
        <button
            type="button"
            wire:click="removeSquad"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-white/10 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/></svg>
            Remove last empty squad
        </button>
    </div>

    {{-- Layout: unassigned column on the left (sticky-ish), squads on the right. --}}
    <div
        class="mt-6 grid gap-4"
        style="grid-template-columns: minmax(240px, 1fr) repeat({{ max(1, $squadKeys->count()) }}, minmax(240px, 1fr));"
        x-data="squadBoard()"
        x-init="init()"
    >
        {{-- UNASSIGNED column --}}
        <div class="flex h-full flex-col rounded-2xl border border-dashed border-gray-300 bg-gray-50 dark:border-white/10 dark:bg-gray-900/40">
            <div class="flex items-baseline justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Unassigned</h3>
                <span class="text-xs text-gray-500">{{ $unassigned->count() }}</span>
            </div>
            <div
                class="squad-dropzone flex-1 space-y-2 p-3 min-h-[120px]"
                data-squad="0"
            >
                @foreach ($unassigned as $entry)
                    @include('filament.admin.resources.events.pages._squad-card', ['entry' => $entry, 'event' => $event])
                @endforeach
            </div>
        </div>

        {{-- SQUAD columns --}}
        @foreach ($squadKeys as $squadNumber)
            @php $entries = $grouped[$squadNumber]; @endphp
            <div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-baseline justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-white/10">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Squad {{ $squadNumber }}</h3>
                    <span class="text-xs text-gray-500">{{ $entries->count() }} {{ \Illuminate\Support\Str::plural('shooter', $entries->count()) }}</span>
                </div>
                <div
                    class="squad-dropzone flex-1 space-y-2 p-3 min-h-[120px]"
                    data-squad="{{ $squadNumber }}"
                >
                    @foreach ($entries as $entry)
                        @include('filament.admin.resources.events.pages._squad-card', ['entry' => $entry, 'event' => $event])
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- SortableJS via CDN — keeps the build pipeline simple. --}}
    {{-- The Alpine wrapper rebuilds Sortable instances after each Livewire --}}
    {{-- re-render so newly added/removed columns stay drag-enabled.      --}}
    @assets
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    @endassets

    @script
        <script>
            window.squadBoard = function () {
                return {
                    sortables: [],

                    init() {
                        this.bind();

                        // Re-bind after every Livewire roundtrip so freshly
                        // rendered columns become drop targets.
                        Livewire.hook('morph.updated', () => this.bind());
                    },

                    bind() {
                        // Tear down stale instances before re-binding.
                        this.sortables.forEach((s) => { try { s.destroy(); } catch (e) {} });
                        this.sortables = [];

                        const zones = this.$el.querySelectorAll('.squad-dropzone');
                        zones.forEach((zone) => {
                            this.sortables.push(new Sortable(zone, {
                                group: 'pprc-squads',
                                animation: 150,
                                ghostClass: 'opacity-40',
                                dragClass: 'ring-2',
                                forceFallback: true,
                                fallbackOnBody: true,
                                onEnd: (evt) => {
                                    const targetSquad = parseInt(evt.to.dataset.squad, 10);
                                    const entryId = parseInt(evt.item.dataset.entryId, 10);
                                    if (! Number.isFinite(entryId) || ! Number.isFinite(targetSquad)) {
                                        return;
                                    }
                                    // Revert SortableJS's optimistic DOM
                                    // move so Livewire's morph algorithm
                                    // sees a consistent starting state.
                                    // The re-render will then apply the
                                    // authoritative server-side placement.
                                    if (evt.from !== evt.to || evt.oldIndex !== evt.newIndex) {
                                        const ref = evt.from.children[evt.oldIndex] || null;
                                        evt.from.insertBefore(evt.item, ref);
                                    }
                                    @this.call('assignSquad', entryId, targetSquad);
                                },
                            }));
                        });
                    },
                };
            };
        </script>
    @endscript
</x-filament-panels::page>
