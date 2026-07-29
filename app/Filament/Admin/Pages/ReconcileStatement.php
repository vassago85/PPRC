<?php

namespace App\Filament\Admin\Pages;

use App\Services\Payments\BankStatementParser;
use App\Services\Payments\PaymentSettler;
use App\Services\Payments\StatementLine;
use App\Services\Payments\StatementReconciliation;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Upload a bank statement export and reconcile the whole month at once.
 *
 * Each money-in line goes past the same resolver the single-line lookup uses.
 * Lines that resolve to exactly one certain, still-owing item for exactly the
 * amount received can be settled in a batch; everything else is listed for
 * someone to decide, because a wrongly settled entry costs far more to unpick
 * than one done by hand.
 *
 * Nothing about the uploaded file is stored. It is parsed in memory, reviewed,
 * and forgotten when the page is left.
 */
class ReconcileStatement extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Members';

    protected static ?int $navigationSort = 18;

    protected static ?string $navigationLabel = 'Reconcile statement';

    protected static ?string $title = 'Reconcile statement';

    protected static ?string $slug = 'reconcile-statement';

    protected string $view = 'filament.admin.pages.reconcile-statement';

    /** The uploaded CSV. */
    public $file = null;

    /** @var array<int, array<string, mixed>> */
    public array $reviews = [];

    /** @var array<string, int> */
    public array $summary = [];

    /** @var array<string, int> Money-out and unusable rows we skipped. */
    public array $skipped = [];

    public bool $parsed = false;

    /** Statuses currently shown, so a long statement can be worked through. */
    public string $filter = 'all';

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

        Notification::make()->success()
            ->title('Statement read')
            ->body($this->summary['lines'].' money-in '.str('line')->plural($this->summary['lines'])
                .' found, '.$this->summary['ready'].' ready to settle.')
            ->send();
    }

    public function clear(): void
    {
        $this->reset(['file', 'reviews', 'summary', 'skipped', 'parsed', 'filter']);
    }

    /**
     * Settle one candidate against one statement line.
     */
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

    /**
     * Settle every line the resolver was certain about. Each one still had to
     * resolve to a single item owing exactly what the bank received.
     */
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
        if ($this->filter === 'all') {
            return $this->reviews;
        }

        return array_values(array_filter(
            $this->reviews,
            fn (array $review) => $review['status'] === $this->filter,
        ));
    }

    public function canSettleKind(string $kind): bool
    {
        return app(PaymentSettler::class)->canSettle($kind, auth()->user());
    }

    /**
     * Re-resolve a single line after acting on it, so its status and remaining
     * candidates reflect what just happened without re-reading the whole file.
     */
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
