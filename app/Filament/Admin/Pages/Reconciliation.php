<?php

namespace App\Filament\Admin\Pages;

use App\Services\Payments\BankStatementParser;
use App\Services\Payments\PaymentMatch;
use App\Services\Payments\PaymentReferenceResolver;
use App\Services\Payments\PaymentSettler;
use App\Services\Payments\StatementLine;
use App\Services\Payments\StatementReconciliation;
use App\Filament\Admin\Resources\Events\EventResource;
use App\Filament\Admin\Resources\Members\MemberResource;
use App\Filament\Admin\Resources\MembershipPayments\MembershipPaymentResource;
use App\Filament\Admin\Resources\ShopOrders\ShopOrderResource;
use App\Models\EventRegistration;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Reconciliation desk — one screen, two tabs.
 *
 * Old routes /admin/find-payment and /admin/reconcile-statement redirect here
 * so anyone with a bookmark or an email link still lands somewhere sensible.
 *
 * "Find one line" is what the treasurer reaches for after a single WhatsApp
 * screenshot from a member. "Reconcile statement" is the same resolver run
 * against a whole CSV. Both used to be two pages that did the same job at
 * different scales, so consolidating them removes the "which tool do I
 * want?" moment.
 */
class Reconciliation extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|UnitEnum|null $navigationGroup = 'Money';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Reconciliation';

    protected static ?string $title = 'Reconciliation';

    protected static ?string $slug = 'reconciliation';

    protected string $view = 'filament.admin.pages.reconciliation';

    /** Which side of the desk is open. */
    public string $tab = 'find';

    // ---- Find one line ---------------------------------------------------
    public string $line = '';

    public string $amount = '';

    /** @var array<int, array<string, mixed>> */
    public array $results = [];

    public bool $searched = false;

    // ---- Reconcile statement --------------------------------------------
    public $file = null;

    /** @var array<int, array<string, mixed>> */
    public array $reviews = [];

    /** @var array<string, int> */
    public array $summary = [];

    /** @var array<string, int> */
    public array $skipped = [];

    public bool $parsed = false;

    public string $filter = 'all';

    /** @var array<int, int> */
    public array $ignored = [];

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

        // Accept ?tab=find|statement so the two legacy pages can redirect
        // straight to the right side of the desk. Anything else silently falls
        // back to "find" (the more common day-to-day tool).
        $requested = request()->query('tab');
        if (is_string($requested) && in_array($requested, ['find', 'statement'], true)) {
            $this->tab = $requested;
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['find', 'statement'], true) ? $tab : 'find';
    }

    // ---- Find one line ---------------------------------------------------

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

    public function clearFind(): void
    {
        $this->line = '';
        $this->amount = '';
        $this->results = [];
        $this->searched = false;
    }

    public function amountCents(): ?int
    {
        $digits = preg_replace('/[^0-9.]/', '', $this->amount);

        if ($digits === null || $digits === '' || $digits === '.') {
            return null;
        }

        return (int) round(((float) $digits) * 100);
    }

    public function markEntryPaid(int $id): void
    {
        $this->settle(PaymentMatch::MATCH_ENTRY.':'.$id);
    }

    public function confirmMembershipPayment(int $id): void
    {
        $this->settle(PaymentMatch::MEMBERSHIP_PAYMENT.':'.$id);
    }

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
                ? MemberResource::getUrl('view', ['record' => $match->memberId])
                : null,
        };
    }

    /**
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

    public function canMarkEntries(): bool
    {
        return (bool) auth()->user()?->can('events.registrations.manage');
    }

    public function canConfirmMemberships(): bool
    {
        return (bool) auth()->user()?->can('payments.eft.confirm');
    }

    // ---- Reconcile statement --------------------------------------------

    public function updatedFile(): void
    {
        $this->review();
    }

    public function review(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'max:4096', 'mimes:csv,txt'],
        ], attributes: ['file' => 'statement file']);

        try {
            $parsed = app(BankStatementParser::class)->parse(
                (string) file_get_contents($this->file->getRealPath()),
            );
        } catch (\RuntimeException $e) {
            $this->reset(['reviews', 'summary', 'skipped', 'parsed']);

            Notification::make()->danger()
                ->title('Could not read that statement')
                ->body($e->getMessage())
                ->persistent()
                ->send();

            return;
        }

        $reconciliation = app(StatementReconciliation::class);

        $this->reviews = $reconciliation->review($parsed->credits);
        $this->summary = $reconciliation->summary($this->reviews);
        $this->skipped = ['debits' => $parsed->debits, 'ignored' => $parsed->ignored];
        $this->parsed = true;
        $this->filter = 'all';
        $this->ignored = [];

        Notification::make()->success()
            ->title('Statement read')
            ->body($this->summary['lines'].' money-in '.str('line')->plural($this->summary['lines'])
                .' found, '.$this->summary['ready'].' ready to settle.')
            ->send();
    }

    public function clearStatement(): void
    {
        $this->reset(['file', 'reviews', 'summary', 'skipped', 'parsed', 'filter', 'ignored']);
    }

    public function ignore(int $row): void
    {
        if (! in_array($row, $this->ignored, true)) {
            $this->ignored[] = $row;
        }
    }

    public function restore(int $row): void
    {
        $this->ignored = array_values(array_filter($this->ignored, fn (int $r) => $r !== $row));
    }

    public function ignoreAllVisible(): void
    {
        foreach ($this->visibleReviews() as $review) {
            $this->ignore((int) $review['row']);
        }
    }

    public function isIgnored(int $row): bool
    {
        return in_array($row, $this->ignored, true);
    }

    public function ignoredCount(): int
    {
        return count($this->ignored);
    }

    public function apply(int $row, string $key): void
    {
        $settler = app(PaymentSettler::class);

        try {
            $message = $settler->settle($key, auth()->user());
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\RuntimeException $e) {
            Notification::make()->warning()
                ->title('Nothing settled')
                ->body($e->getMessage())
                ->send();

            $this->refreshRow($row);

            return;
        }

        Notification::make()->success()
            ->title('Payment recorded')
            ->body($message)
            ->send();

        $this->refreshRow($row);
    }

    public function applyAllReady(): void
    {
        $settler = app(PaymentSettler::class);
        $actor = auth()->user();

        $settled = 0;
        $failed = 0;

        foreach ($this->reviews as $index => $review) {
            if ($review['status'] !== StatementReconciliation::READY || $review['apply'] === null) {
                continue;
            }
            if ($this->isIgnored((int) $review['row'])) {
                continue;
            }
            if (! $settler->canSettle(explode(':', $review['apply'])[0], $actor)) {
                $failed++;

                continue;
            }

            try {
                $settler->settle($review['apply'], $actor);
                $settled++;
            } catch (\Throwable) {
                $failed++;

                continue;
            }

            $this->reviews[$index] = $this->reviewFor($review);
        }

        $this->summary = app(StatementReconciliation::class)->summary($this->reviews);

        Notification::make()
            ->{$settled > 0 ? 'success' : 'warning'}()
            ->title($settled.' '.str('payment')->plural($settled).' recorded')
            ->body($failed > 0
                ? $failed.' could not be settled and are still listed for review.'
                : 'Everything the resolver was certain about is now settled.')
            ->send();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function visibleReviews(): array
    {
        if ($this->filter === 'ignored') {
            return array_values(array_filter(
                $this->reviews,
                fn (array $review) => $this->isIgnored((int) $review['row']),
            ));
        }

        return array_values(array_filter(
            $this->reviews,
            fn (array $review) => ! $this->isIgnored((int) $review['row'])
                && ($this->filter === 'all' || $review['status'] === $this->filter),
        ));
    }

    public function canSettleKind(string $kind): bool
    {
        return app(PaymentSettler::class)->canSettle($kind, auth()->user());
    }

    protected function refreshRow(int $row): void
    {
        foreach ($this->reviews as $index => $review) {
            if ((int) $review['row'] !== $row) {
                continue;
            }

            $this->reviews[$index] = $this->reviewFor($review);
            break;
        }

        $this->summary = app(StatementReconciliation::class)->summary($this->reviews);
    }

    /**
     * @param  array<string, mixed>  $review
     * @return array<string, mixed>
     */
    protected function reviewFor(array $review): array
    {
        return app(StatementReconciliation::class)->reviewLine(new StatementLine(
            row: (int) $review['row'],
            date: $review['date'] ? Carbon::parse((string) $review['date']) : null,
            amountCents: (int) $review['amount_cents'],
            description: (string) $review['description'],
        ));
    }
}
