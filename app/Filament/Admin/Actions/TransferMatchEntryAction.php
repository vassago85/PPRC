<?php

namespace App\Filament\Admin\Actions;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Filament\Admin\Support\MatchCreditSettlement;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\Events\MatchEntryTransferService;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\ValidationException;

/**
 * Takes a shooter who has paid for a match they can no longer shoot and either
 * holds their money as a credit or puts them straight into another match.
 *
 * Both routes go through the credit ledger — see MatchEntryTransferService for
 * why the entry is never simply re-pointed at a different event.
 */
class TransferMatchEntryAction
{
    public static function make(string $name = 'transfer'): Action
    {
        return Action::make($name)
            ->label('Transfer / credit')
            ->icon('heroicon-o-arrows-right-left')
            ->color('warning')
            ->modalWidth('lg')
            ->modalHeading(fn (EventRegistration $record) => 'Transfer '.$record->shooterName().'\'s entry')
            ->modalDescription(fn (EventRegistration $record) => 'They paid R '
                .number_format((int) ($record->effectiveFeeCents() ?? 0) / 100, 2)
                .'. This entry will be cancelled and that amount held as a credit in their name.')
            ->modalSubmitActionLabel('Transfer')
            ->visible(fn (EventRegistration $record) => static::isTransferable($record))
            ->schema([
                Radio::make('destination')
                    ->label('What should happen to the money?')
                    ->options([
                        'match' => 'Put them into another match now',
                        'credit' => 'Hold it as a credit for later',
                    ])
                    ->default('match')
                    ->required()
                    ->live(),
                Select::make('event_id')
                    ->label('Match to move them to')
                    ->options(fn (EventRegistration $record) => static::matchOptions($record))
                    ->searchable()
                    ->native(false)
                    ->required(fn (Get $get) => $get('destination') === 'match')
                    ->visible(fn (Get $get) => $get('destination') === 'match')
                    ->helperText('If the new match costs more, the difference is left owing and they are emailed the shortfall.'),
                TextInput::make('reason')
                    ->label('Reason (optional)')
                    ->maxLength(150)
                    ->placeholder('Work commitment, injury, double-booked…'),
            ])
            ->action(function (EventRegistration $record, array $data): void {
                static::run($record, $data);
            });
    }

    public static function isTransferable(EventRegistration $record): bool
    {
        if (! auth()->user()?->can('events.registrations.manage')) {
            return false;
        }

        return $record->paid_at !== null
            && $record->status !== EventRegistrationStatus::Cancelled
            && (int) ($record->effectiveFeeCents() ?? 0) > 0;
    }

    private static function run(EventRegistration $record, array $data): void
    {
        $service = app(MatchEntryTransferService::class);
        $reason = filled($data['reason'] ?? null) ? $data['reason'] : null;

        try {
            if (($data['destination'] ?? 'match') === 'credit') {
                $credit = $service->release($record, $reason, auth()->user());

                Notification::make()->success()
                    ->title('Held as a credit')
                    ->body($credit->payeeName().' now holds R '
                        .number_format($credit->amount_cents / 100, 2)
                        .' towards a future match. Their entry here has been cancelled.')
                    ->send();

                return;
            }

            $target = Event::findOrFail($data['event_id']);
            $entry = $service->transfer($record, $target, $reason, auth()->user());

            Notification::make()->success()
                ->title('Moved to '.$target->title)
                ->body(MatchCreditSettlement::announce($entry))
                ->send();
        } catch (ValidationException $e) {
            Notification::make()->danger()
                ->title('Could not transfer this entry')
                ->body(collect($e->errors())->flatten()->first() ?? $e->getMessage())
                ->send();
        }
    }

    /**
     * Matches still to come, so a paid entry cannot be transferred into a match
     * that has already been shot and reported on.
     *
     * @return array<int, string>
     */
    private static function matchOptions(EventRegistration $record): array
    {
        return Event::query()
            ->whereKeyNot($record->event_id)
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
