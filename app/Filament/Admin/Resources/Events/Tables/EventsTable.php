<?php

namespace App\Filament\Admin\Resources\Events\Tables;

use App\Enums\EventState;
use App\Enums\EventStatus;
use App\Enums\MatchEntryAudience;
use App\Filament\Admin\Actions\EnterMatchAction;
use App\Filament\Admin\Resources\Events\EventResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\Events\MatchEntryDeadCenterExporter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

/**
 * Matches list — reference implementation of the x-ui rebuild.
 *
 * The state column is the derived EventState (Draft / Entries open / Entries
 * closed / Shot / Results published / Cancelled), not the stored EventStatus,
 * so what an admin reads matches what's really going on.
 *
 * Only one primary action is visible on each row and it is chosen from the
 * state:
 *   Draft            → Publish
 *   Entries open     → Squadding
 *   Entries closed   → Squadding (still worth reviewing before it's shot)
 *   Shot             → Publish results
 *   Results          → Results
 * Everything else — Enter match, Export, Report, Edit, Delete — moves into
 * the ⋯ action group, so no row ever carries three inline text links.
 *
 * Delete is behind a required-confirmation modal and is never the top-right
 * red button on the page.
 */
class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('start_date', 'desc')
            // The bare "No records" line is a poor first-run answer when a
            // fresh club has yet to schedule anything.
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->emptyStateHeading('No matches yet')
            ->emptyStateDescription('Schedule a match to open entries and publish results here.')
            ->modifyQueryUsing(fn ($query) => $query->withCount([
                'registrations',
                'registrations as new_entries_count' => fn ($q) => $q
                    ->where('created_at', '>=', now()->subDays(EventRegistration::NEW_SIGNUP_WINDOW_DAYS)),
            ]))
            ->columns([
                TextColumn::make('start_date')
                    ->date('D d M Y')
                    ->sortable()
                    ->label('Date'),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (Event $record) => $record->matchFormat?->short_name),

                // Derived state pill — the vocabulary the admin actually reads.
                TextColumn::make('state')
                    ->label('State')
                    ->badge()
                    ->state(fn (Event $record) => EventState::for($record))
                    ->formatStateUsing(fn (EventState $state) => $state->label())
                    ->color(fn (EventState $state) => match ($state->variant()) {
                        'ok' => 'success',
                        'warn' => 'warning',
                        'info' => 'info',
                        'crit' => 'danger',
                        default => 'gray',
                    }),

                // Entries against capacity as a real meter, not a bare count.
                ViewColumn::make('entries_meter')
                    ->label('Entries')
                    ->view('filament.admin.resources.events.columns.entries-meter'),

                TextColumn::make('new_entries_count')
                    ->label('New')
                    ->tooltip('Entries in the last '.EventRegistration::NEW_SIGNUP_WINDOW_DAYS.' days')
                    ->state(fn (Event $record): ?string => ($record->new_entries_count ?? 0) > 0
                        ? '+'.$record->new_entries_count
                        : null)
                    ->color('success')
                    ->placeholder('—'),

                TextColumn::make('match_director_name')
                    ->label('MD')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->formatStateUsing(fn (?string $state, Event $record): string => $record->matchDirectorDisplay() ?: '—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(EventStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
                SelectFilter::make('match_format_id')
                    ->label('Format')
                    ->relationship('matchFormat', 'name'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                // Exactly one primary, state-dependent action per row.
                static::primaryAction(),

                // Everything else lives behind the ⋯ menu.
                ActionGroup::make(array_filter([
                    EnterMatchAction::make(),
                    static::exportAction(),
                    Action::make('report')
                        ->label('Match report')
                        ->icon('heroicon-o-banknotes')
                        ->visible(fn () => auth()->user()?->can('events.view'))
                        ->url(fn (Event $record) => EventResource::getUrl('report', ['record' => $record])),
                    EditAction::make(),
                    DeleteAction::make()
                        ->modalHeading('Delete this match?')
                        ->requiresConfirmation(),
                ]))
                    ->label('More')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->size(Size::Small)
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * The single visible action per row, driven by state.
     */
    protected static function primaryAction(): Action
    {
        return Action::make('state_action')
            ->label(fn (Event $record) => match (EventState::for($record)) {
                EventState::Draft => 'Publish',
                EventState::EntriesOpen, EventState::EntriesClosed => 'Squadding',
                EventState::Shot => 'Publish results',
                EventState::ResultsPublished => 'Results',
                EventState::Cancelled => 'View',
            })
            ->icon(fn (Event $record) => match (EventState::for($record)) {
                EventState::Draft => 'heroicon-o-megaphone',
                EventState::EntriesOpen, EventState::EntriesClosed => 'heroicon-o-user-group',
                EventState::Shot => 'heroicon-o-trophy',
                EventState::ResultsPublished => 'heroicon-o-chart-bar',
                EventState::Cancelled => 'heroicon-o-eye',
            })
            ->size(Size::Small)
            ->color(fn (Event $record) => match (EventState::for($record)) {
                EventState::Shot => 'warning',
                EventState::Draft => 'primary',
                default => 'gray',
            })
            ->url(fn (Event $record) => match (EventState::for($record)) {
                EventState::Draft => null,
                EventState::EntriesOpen, EventState::EntriesClosed => EventResource::getUrl('squads', ['record' => $record]),
                EventState::Shot, EventState::ResultsPublished => EventResource::getUrl('report', ['record' => $record]),
                EventState::Cancelled => EventResource::getUrl('edit', ['record' => $record]),
            })
            ->action(function (Event $record) {
                if (EventState::for($record) === EventState::Draft
                    && auth()->user()?->can('events.publish')) {
                    $record->update([
                        'status' => EventStatus::Published,
                        'published_at' => $record->published_at ?? now(),
                    ]);
                    Notification::make()->success()
                        ->title('Match published')
                        ->send();
                }
            })
            ->requiresConfirmation(fn (Event $record) => EventState::for($record) === EventState::Draft)
            ->modalHeading('Publish this match?')
            ->modalDescription('Members will see the match on the public matches page and registrations will open according to your settings.');
    }

    protected static function exportAction(): Action
    {
        return Action::make('export_entries')
            ->label('Export entries')
            ->icon('heroicon-o-arrow-down-tray')
            ->visible(fn () => auth()->user()?->can('events.view'))
            ->schema([
                Select::make('audience')
                    ->label('Who to include')
                    ->options(MatchEntryAudience::options())
                    ->default(MatchEntryAudience::Confirmed->value)
                    ->required(),
            ])
            ->action(function (Event $record, array $data) {
                $audience = MatchEntryAudience::from($data['audience']);
                $exporter = app(MatchEntryDeadCenterExporter::class);

                if ($exporter->rows($record, $audience) === []) {
                    Notification::make()->warning()
                        ->title('Nothing to export')
                        ->body('No entries match that filter.')
                        ->send();

                    return null;
                }

                return $exporter->download($record, $audience);
            });
    }
}
