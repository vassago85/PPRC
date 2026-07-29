<?php

namespace App\Filament\Admin\Actions;

use App\Enums\EventStatus;
use App\Filament\Admin\Support\MatchCreditSettlement;
use App\Models\Event;
use App\Models\MatchCredit;
use App\Services\Events\MatchEntryTransferService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Spends a credit straight from the ledger: picks the match, enters the shooter
 * if they are not already on the list, and settles what the credit covers.
 *
 * This is what "mark used" was standing in for — that only moved a flag and
 * left an admin to enter the shooter and mark them paid by hand.
 */
class UseMatchCreditAction
{
    public static function make(string $name = 'use_on_match'): Action
    {
        return Action::make($name)
            ->label('Use on a match')
            ->icon('heroicon-o-ticket')
            ->color('success')
            ->modalWidth('lg')
            ->modalHeading(fn (MatchCredit $record) => 'Use '.$record->payeeName().'\'s credit')
            ->modalDescription(fn (MatchCredit $record) => 'R '
                .number_format($record->amount_cents / 100, 2)
                .' available. They will be entered if they are not on the list already.')
            ->modalSubmitActionLabel('Enter and settle')
            ->visible(fn (MatchCredit $record) => $record->isAvailable()
                && auth()->user()?->can('events.registrations.manage'))
            ->schema([
                Select::make('event_id')
                    ->label('Match')
                    ->options(fn () => static::matchOptions())
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->helperText('If the match costs more than the credit, the difference is left owing and they are emailed the shortfall.'),
            ])
            ->action(function (MatchCredit $record, array $data): void {
                static::run($record, $data);
            });
    }

    private static function run(MatchCredit $credit, array $data): void
    {
        try {
            $event = Event::findOrFail($data['event_id']);

            $entry = app(MatchEntryTransferService::class)
                ->applyToEvent($credit, $event, auth()->user());

            Notification::make()->success()
                ->title($credit->payeeName().' is entered for '.$event->title)
                ->body(MatchCreditSettlement::announce($entry))
                ->send();
        } catch (ValidationException $e) {
            Notification::make()->danger()
                ->title('Could not use this credit')
                ->body(collect($e->errors())->flatten()->first() ?? $e->getMessage())
                ->send();
        }
    }

    /**
     * @return array<int, string>
     */
    private static function matchOptions(): array
    {
        return Event::query()
            ->whereDate('start_date', '>=', today())
            ->whereNotIn('status', [EventStatus::Completed->value, EventStatus::Cancelled->value])
            ->orderBy('start_date')
            ->get()
            ->mapWithKeys(fn (Event $event) => [
                $event->id => $event->start_date?->format('D d M Y').' — '.$event->title,
            ])
            ->all();
    }
}
