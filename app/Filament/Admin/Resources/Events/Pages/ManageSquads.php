<?php

namespace App\Filament\Admin\Resources\Events\Pages;

use App\Filament\Admin\Resources\Events\EventResource;
use App\Models\Event;
use App\Models\EventRegistration;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * Drag-and-drop squadding board for an event.
 *
 * The page renders one column per visible squad plus an "Unassigned" column.
 * SortableJS (loaded via CDN in the view) handles the actual drag interaction
 * and calls `assignSquad()` on drop so the move is persisted immediately.
 */
class ManageSquads extends Page
{
    use InteractsWithRecord;

    protected static string $resource = EventResource::class;

    protected string $view = 'filament.admin.resources.events.pages.manage-squads';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $title = 'Squadding';

    /**
     * Squads currently visible in the UI. Always at least 1; grows when the
     * admin clicks "Add squad" or pulls in higher squad numbers from
     * existing data.
     */
    public int $visibleSquadCount = 4;

    public function mount(int|string $record): void
    {
        abort_unless((bool) auth()->user()?->can('events.update'), 403);

        $this->record = $this->resolveRecord($record);

        $maxAssigned = (int) $this->getRecord()
            ->registrations()
            ->max('squad_number');

        $this->visibleSquadCount = max(4, $maxAssigned, 1);
    }

    public static function canAccess(array $parameters = []): bool
    {
        return (bool) auth()->user()?->can('events.update');
    }

    public function getRecord(): Event
    {
        /** @var Event */
        return $this->record;
    }

    /**
     * @return array<int, Collection<int, EventRegistration>>
     */
    public function getEntriesBySquad(): array
    {
        $entries = $this->getRecord()
            ->registrations()
            ->with('member:id,first_name,last_name,membership_number')
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->orderByRaw('firing_order IS NULL, firing_order')
            ->orderBy('id')
            ->get();

        $grouped = [];
        for ($i = 1; $i <= $this->visibleSquadCount; $i++) {
            $grouped[$i] = collect();
        }
        $grouped[0] = collect(); // 0 == unassigned

        foreach ($entries as $entry) {
            $key = $entry->squad_number;
            if ($key === null || $key === 0) {
                $grouped[0]->push($entry);
            } else {
                if (! isset($grouped[$key])) {
                    $grouped[$key] = collect();
                }
                $grouped[$key]->push($entry);
            }
        }

        return $grouped;
    }

    /**
     * Persist a squad assignment when an entry card is dropped into a column.
     *
     * @param  int  $entryId    The registration row to move.
     * @param  int  $squadNumber 0 for the unassigned column, otherwise the
     *                          target squad's 1-based number.
     */
    public function assignSquad(int $entryId, int $squadNumber): void
    {
        $entry = $this->getRecord()->registrations()->whereKey($entryId)->first();
        if (! $entry) {
            return;
        }

        $entry->update([
            'squad_number' => $squadNumber > 0 ? $squadNumber : null,
            // Clear firing order so it doesn't stick when shooters move
            // between squads; admins can re-set it after the layout settles.
            'firing_order' => null,
        ]);

        if ($squadNumber > $this->visibleSquadCount) {
            $this->visibleSquadCount = $squadNumber;
        }
    }

    public function addSquad(): void
    {
        $this->visibleSquadCount = min($this->visibleSquadCount + 1, 20);
    }

    public function removeSquad(): void
    {
        if ($this->visibleSquadCount <= 1) {
            return;
        }

        // Don't drop a squad that still has shooters in it; bounce them
        // back to "Unassigned" first so nothing silently disappears.
        $highest = $this->visibleSquadCount;
        $count = $this->getRecord()
            ->registrations()
            ->where('squad_number', $highest)
            ->count();

        if ($count > 0) {
            Notification::make()
                ->warning()
                ->title("Squad {$highest} still has shooters")
                ->body('Move them to another squad first, then remove the column.')
                ->send();

            return;
        }

        $this->visibleSquadCount--;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to match')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => EventResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'Squadding';
    }

    public function getTitle(): string
    {
        return 'Squadding — '.$this->getRecord()->title;
    }
}
