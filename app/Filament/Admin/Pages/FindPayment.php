<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\Events\EventResource;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Filament\Admin\Resources\MembershipPayments\MembershipPaymentResource;
use App\Filament\Admin\Resources\ShopOrders\ShopOrderResource;
use App\Models\EventRegistration;
use App\Services\Payments\PaymentMatch;
use App\Services\Payments\PaymentReferenceResolver;
use App\Services\Payments\PaymentSettler;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Reconciliation desk: paste a line off the bank statement exactly as it
 * appears and find out who paid and what for.
 *
 * This exists because a correct reference is not enough. Banks bolt their own
 * narration onto the front, strip the separators, or replace the reference with
 * the payer's name; and members quote whichever reference they still have in
 * their banking app, which is usually the membership one they were given when
 * they joined. So the page identifies the *person* as well as the reference and
 * offers everything they owe, ranked, for a human to choose from. It never
 * settles anything on a guess.
 */
class FindPayment extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Members';

    protected static ?int $navigationSort = 17;

    protected static ?string $navigationLabel = 'Find payment';

    protected static ?string $title = 'Find payment';

    protected static ?string $slug = 'find-payment';

    protected string $view = 'filament.admin.pages.find-payment';

    /** The bank statement line, pasted verbatim. */
    public string $line = '';

    /** Optional amount off the statement — only ever used as a hint. */
    public string $amount = '';

    /** @var array<int, array<string, mixed>> */
    public array $results = [];

    public bool $searched = false;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('payments.eft.confirm')
            || $user?->can('payments.view')
            || $user?->can('events.registrations.manage'));
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function find(): void
    {
        $this->searched = true;

        if (trim($this->line) === '') {
            $this->results = [];

            return;
        }

        $matches = app(PaymentReferenceResolver::class)->resolve($this->line);

        $eventIds = $this->eventIdsFor($matches);

        $this->results = array_map(
            fn (PaymentMatch $match) => $match->withUrl($this->urlFor($match, $eventIds))->toArray(),
            $matches,
        );
    }

    public function clear(): void
    {
        $this->line = '';
        $this->amount = '';
        $this->results = [];
        $this->searched = false;
    }

    /**
     * The amount off the statement, in cents. Accepts "450", "R 450.00" and
     * "1,234.56" because that's what people paste.
     */
    public function amountCents(): ?int
    {
        $digits = preg_replace('/[^0-9.]/', '', $this->amount);

        if ($digits === null || $digits === '' || $digits === '.') {
            return null;
        }

        return (int) round(((float) $digits) * 100);
    }

    public function canMarkEntries(): bool
    {
        return (bool) auth()->user()?->can('events.registrations.manage');
    }

    public function canConfirmMemberships(): bool
    {
        return (bool) auth()->user()?->can('payments.eft.confirm');
    }

    public function markEntryPaid(int $id): void
    {
        $this->settle(PaymentMatch::MATCH_ENTRY.':'.$id);
    }

    public function confirmMembershipPayment(int $id): void
    {
        $this->settle(PaymentMatch::MEMBERSHIP_PAYMENT.':'.$id);
    }

    /**
     * Recording the money is shared with the statement upload, so the two can't
     * drift on permissions, payment method or confirmation emails.
     */
    protected function settle(string $key): void
    {
        try {
            $message = app(PaymentSettler::class)->settle($key, auth()->user());
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\RuntimeException $e) {
            Notification::make()->warning()
                ->title('Nothing settled')
                ->body($e->getMessage())
                ->send();

            $this->find();

            return;
        }

        Notification::make()->success()
            ->title('Payment recorded')
            ->body($message)
            ->send();

        $this->find();
    }

    /**
     * @param  Collection<int, int>  $eventIds
     */
    protected function urlFor(PaymentMatch $match, Collection $eventIds): ?string
    {
        return match ($match->kind) {
            PaymentMatch::MATCH_ENTRY => ($eventId = $eventIds->get($match->id))
                ? EventResource::getUrl('report', ['record' => $eventId])
                : null,
            PaymentMatch::MEMBERSHIP_PAYMENT => $match->reference
                ? MembershipPaymentResource::getUrl('index', ['tableSearch' => $match->reference])
                : MembershipPaymentResource::getUrl('index'),
            PaymentMatch::SHOP_ORDER => ShopOrderResource::getUrl('index'),
            default => $match->memberId
                ? MemberResource::getUrl('edit', ['record' => $match->memberId])
                : null,
        };
    }

    /**
     * Entry id => match id, resolved in one query for the whole result set so
     * building the links doesn't turn into a query per row.
     *
     * @param  array<int, PaymentMatch>  $matches
     * @return Collection<int, int>
     */
    protected function eventIdsFor(array $matches): Collection
    {
        $entryIds = collect($matches)
            ->filter(fn (PaymentMatch $match) => $match->kind === PaymentMatch::MATCH_ENTRY)
            ->map(fn (PaymentMatch $match) => $match->id)
            ->all();

        if ($entryIds === []) {
            return new Collection;
        }

        return EventRegistration::query()
            ->whereIn('id', $entryIds)
            ->pluck('event_id', 'id');
    }
}
