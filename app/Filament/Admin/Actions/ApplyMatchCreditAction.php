<?php

namespace App\Filament\Admin\Actions;

use App\Filament\Admin\Support\MatchCreditSettlement;
use App\Models\EventRegistration;
use App\Models\MatchCredit;
use App\Services\Events\MatchEntryTransferService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Settles an entry that is waiting on money from a credit the club is already
 * holding for that shooter, rather than asking them to pay again.
 *
 * Only offered when they actually hold one, so the button's presence is the
 * signal — an admin does not have to go and check the ledger.
 */
class ApplyMatchCreditAction
{
    public static function make(string $name = 'apply_credit'): Action
    {
        return Action::make($name)
            ->label('Apply credit')
            ->icon('heroicon-o-ticket')
            ->color('success')
            ->modalWidth('md')
            ->modalHeading(fn (EventRegistration $record) => 'Apply a credit for '.$record->shooterName())
            ->modalDescription(fn (EventRegistration $record) => 'This entry is waiting on R '
                .number_format($record->outstandingCents() / 100, 2).'.')
            ->modalSubmitActionLabel('Apply credit')
            ->visible(fn (EventRegistration $record) => static::isAvailableFor($record))
            ->schema([
                Select::make('credit_id')
                    ->label('Credit to use')
                    ->options(fn (EventRegistration $record) => static::creditOptions($record))
                    ->default(fn (EventRegistration $record) => array_key_first(static::creditOptions($record)))
                    ->required()
                    ->native(false),
            ])
            ->action(function (EventRegistration $record, array $data): void {
                static::run($record, $data);
            });
    }

    public static function isAvailableFor(EventRegistration $record): bool
    {
        if (! auth()->user()?->can('events.registrations.manage')) {
            return false;
        }

        if (! $record->awaitingPayment()) {
            return false;
        }

        return app(MatchEntryTransferService::class)->creditsFor($record)->isNotEmpty();
    }

    private static function run(EventRegistration $record, array $data): void
    {
        $service = app(MatchEntryTransferService::class);

        $credit = $service->creditsFor($record)
            ->firstWhere('id', (int) $data['credit_id']);

        if (! $credit) {
            Notification::make()->danger()
                ->title('Credit not available')
                ->body('That credit is no longer available to use.')
                ->send();

            return;
        }

        try {
            $service->settle($credit, $record, auth()->user());

            Notification::make()->success()
                ->title('Credit applied')
                ->body(MatchCreditSettlement::announce($record->refresh()))
                ->send();
        } catch (ValidationException $e) {
            Notification::make()->danger()
                ->title('Could not apply the credit')
                ->body(collect($e->errors())->flatten()->first() ?? $e->getMessage())
                ->send();
        }
    }

    /**
     * @return array<int, string>
     */
    private static function creditOptions(EventRegistration $record): array
    {
        return app(MatchEntryTransferService::class)->creditsFor($record)
            ->mapWithKeys(fn (MatchCredit $credit) => [
                $credit->id => 'R '.number_format($credit->amount_cents / 100, 2)
                    .' — '.($credit->reason ?: 'credit')
                    .' ('.$credit->created_at?->format('d M Y').')',
            ])
            ->all();
    }
}
