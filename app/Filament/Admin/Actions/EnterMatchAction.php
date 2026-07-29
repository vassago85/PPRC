<?php

namespace App\Filament\Admin\Actions;

use App\Enums\EventRegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\Events\MatchEntryPaymentRequestService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

/**
 * Lets whoever is signed in to the admin panel put themselves on the entry
 * list for a match without leaving it.
 *
 * Committee members spend their time in /admin, but the only self-service
 * signup lived on the public match page, so entering a match you were already
 * administering meant logging out of your own workflow to go and find the
 * form. This is the same registration, created from where they already are.
 *
 * Matches require a division by default, so the modal is usually unavoidable —
 * but it opens pre-filled with whatever the shooter entered last time, leaving
 * one button to press. Matches that ask for nothing skip the modal entirely.
 */
class EnterMatchAction
{
    /**
     * Memo key for "does the signed-in user already have an entry". Held on the
     * Event instance rather than statically, so it cannot outlive the request
     * it was resolved in.
     */
    private const ENTRY_MEMO = 'enterMatchActionEntry';

    public static function make(string $name = 'enter_match'): Action
    {
        return Action::make($name)
            ->label(fn (Event $record) => static::entryFor($record) ? "You're entered" : 'Enter')
            ->icon(fn (Event $record) => static::entryFor($record)
                ? 'heroicon-o-check-badge'
                : 'heroicon-o-hand-raised')
            ->color(fn (Event $record) => static::entryFor($record) ? 'success' : 'primary')
            ->disabled(fn (Event $record) => static::entryFor($record) !== null)
            ->visible(fn (Event $record) => static::isAvailableFor($record))
            ->tooltip(fn (Event $record) => static::tooltip($record))
            ->modalHeading(fn (Event $record) => static::opensModal($record)
                ? 'Enter '.$record->title
                : null)
            ->modalDescription(fn (Event $record) => static::closedWarning($record))
            ->modalSubmitActionLabel('Enter me')
            ->fillForm(fn (Event $record) => static::defaults($record))
            ->schema(fn (Event $record) => static::fields($record))
            ->action(function (Event $record, array $data): void {
                static::enter($record, $data);
            });
    }

    /**
     * Only offered to admins who are shooters themselves, and only while the
     * match can still take entries at all.
     */
    public static function isAvailableFor(Event $event): bool
    {
        $user = auth()->user();

        if (! $user?->member) {
            return false;
        }

        if (! $user->can('events.view')) {
            return false;
        }

        return ! $event->isFinished();
    }

    private static function enter(Event $event, array $data): void
    {
        $member = auth()->user()?->member;

        if (! $member) {
            Notification::make()->danger()
                ->title('No member profile')
                ->body('Your login is not linked to a member record, so it cannot be added to the entry list.')
                ->send();

            return;
        }

        // The table has a unique (event_id, member_id) index, so a withdrawal
        // has to be revived rather than replaced — otherwise anyone who ever
        // cancelled an entry could never enter that match again.
        $existing = EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('member_id', $member->id)
            ->first();

        if ($existing && $existing->status !== EventRegistrationStatus::Cancelled) {
            Notification::make()->warning()
                ->title('Already entered')
                ->body('You are on the entry list for '.$event->title.'.')
                ->send();

            return;
        }

        $attributes = [
            'division' => $data['division'] ?? null,
            'category' => $data['category'] ?? null,
            'status' => EventRegistrationStatus::Registered,
            'registered_at' => now(),
        ];

        if ($existing) {
            // Re-price from scratch: the waiver is applied by a creating hook
            // that a revived row never fires.
            $existing->fill($attributes);
            $existing->fee_cents = $member->user?->hasFreeEventEntry() ? 0 : null;
            $existing->save();

            $registration = $existing;
        } else {
            $registration = EventRegistration::create($attributes + [
                'event_id' => $event->id,
                'member_id' => $member->id,
            ]);
        }

        $event->unsetRelation(self::ENTRY_MEMO);

        Notification::make()->success()
            ->title("You're entered for ".$event->title)
            ->body(static::outcome($registration))
            ->send();
    }

    /**
     * Tell them what it cost and whether they need to pay, so a free entry is
     * visibly free and a payable one does not go quietly unpaid.
     */
    private static function outcome(EventRegistration $registration): string
    {
        $fee = (int) ($registration->effectiveFeeCents() ?? 0);

        if ($fee === 0) {
            return 'No entry fee to pay.';
        }

        $amount = 'R '.number_format($fee / 100, 2);

        if (! $registration->owesPayment()) {
            return $amount.' due.';
        }

        try {
            app(MatchEntryPaymentRequestService::class)->send($registration);

            return $amount.' due — banking details and your reference are on the way by email.';
        } catch (\Throwable) {
            return $amount.' due. We could not email your banking details, so use reference '
                .$registration->paymentReference().'.';
        }
    }

    /**
     * Carry the shooter's last division and category forward. People shoot the
     * same division match after match, so re-picking it every time is the part
     * that would make this feel like a form rather than a button.
     *
     * @return array<string, string>
     */
    private static function defaults(Event $event): array
    {
        $memberId = auth()->user()?->member?->id;

        if (! $memberId) {
            return [];
        }

        $previous = EventRegistration::query()
            ->where('member_id', $memberId)
            ->where('event_id', '!=', $event->id)
            ->orderByDesc('registered_at')
            ->orderByDesc('id')
            ->first();

        if (! $previous) {
            return [];
        }

        return array_filter([
            'division' => static::stillOffered($previous->division, $event->registrationDivisionChoices()),
            'category' => static::stillOffered($previous->category, $event->registrationCategoryChoices()),
        ]);
    }

    /**
     * @param  list<string>  $choices
     */
    private static function stillOffered(?string $value, array $choices): ?string
    {
        return $value !== null && in_array($value, $choices, true) ? $value : null;
    }

    /**
     * @return array<int, Select>
     */
    private static function fields(Event $event): array
    {
        $fields = [];

        if ($event->collectsDivisionAtRegistration()) {
            $fields[] = Select::make('division')
                ->options(static::choices($event->registrationDivisionChoices()))
                ->required()
                ->native(false);
        }

        if ($event->collectsCategoryAtRegistration()) {
            $fields[] = Select::make('category')
                ->options(static::choices($event->registrationCategoryChoices()))
                ->required()
                ->native(false);
        }

        return $fields;
    }

    /**
     * @param  list<string>  $values
     * @return array<string, string>
     */
    private static function choices(array $values): array
    {
        return array_combine($values, $values) ?: [];
    }

    private static function opensModal(Event $event): bool
    {
        return static::fields($event) !== [] || static::closedWarning($event) !== null;
    }

    /**
     * Admins can enter a match whose registrations are shut or full — the
     * Squadding and Match report screens already let them add anyone — but not
     * without being told that is what they are doing.
     */
    private static function closedWarning(Event $event): ?string
    {
        if ($event->isRegistrationOpen()) {
            return null;
        }

        if ($event->max_entries !== null
            && $event->registrations()->count() >= $event->max_entries) {
            return 'This match is full ('.$event->max_entries.' entries). Adding yourself will take it over its limit.';
        }

        return 'Registrations are closed for this match. You can still add yourself as an admin.';
    }

    private static function tooltip(Event $event): ?string
    {
        if (static::entryFor($event)) {
            return 'You are on the entry list. Withdraw from the Entries tab.';
        }

        return 'Add yourself to the entry list for this match';
    }

    /**
     * A table row asks four separate closures (label, icon, colour, disabled)
     * whether the user is entered, so the answer is memoised on the record.
     */
    private static function entryFor(Event $event): ?EventRegistration
    {
        $memberId = auth()->user()?->member?->id;

        if (! $memberId) {
            return null;
        }

        if (! $event->relationLoaded(self::ENTRY_MEMO)) {
            $event->setRelation(self::ENTRY_MEMO, EventRegistration::query()
                ->where('event_id', $event->id)
                ->where('member_id', $memberId)
                ->where('status', '!=', EventRegistrationStatus::Cancelled->value)
                ->first());
        }

        return $event->getRelation(self::ENTRY_MEMO);
    }
}
