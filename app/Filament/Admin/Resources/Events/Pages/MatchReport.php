<?php

namespace App\Filament\Admin\Resources\Events\Pages;

use App\Enums\EventRegistrationStatus;
use App\Enums\MatchCreditStatus;
use App\Enums\MatchExpenseCategory;
use App\Enums\MatchPaymentMethod;
use App\Filament\Admin\Resources\Events\EventResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchCredit;
use App\Models\MatchExpense;
use App\Models\Member;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Events\MatchDirectorReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Match director financial report: confirm who paid, and see how much the
 * director should be paid out (collected fees for paying shooters, less the
 * club's per-head levy and, optionally, the non-member surcharge).
 */
class MatchReport extends Page
{
    use InteractsWithRecord;

    protected static string $resource = EventResource::class;

    protected string $view = 'filament.admin.resources.events.pages.match-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $title = 'Match report';

    /** Club levy kept per paying shooter, in Rands (bound to the input). */
    public float $levyRands = 0;

    /** When true, guest fees above the member rate stay with the club. */
    public bool $keepNonMemberDifference = false;

    /** New-expense form state (bound to the "Add cost" row on the report). */
    public string $newExpenseDescription = '';

    public string $newExpensePayee = '';

    public ?float $newExpenseAmount = null;

    /** Default to "Other" so the dropdown is always populated with a safe value. */
    public string $newExpenseCategory = MatchExpenseCategory::Other->value;

    /**
     * Optional user link for the payee — when set, the user's name is written
     * into `payee_name` so the slip stays readable even if the user is removed
     * later. The free-text `newExpensePayee` is kept as a fallback for
     * suppliers who don't have an account.
     */
    public ?int $newExpensePayeeUserId = null;

    public const LEVY_SETTING_KEY = 'matches.director_levy_per_entry_cents';

    public const KEEP_NON_MEMBER_DIFFERENCE_SETTING_KEY = 'matches.director_keep_non_member_difference';

    public function mount(int|string $record): void
    {
        abort_unless((bool) auth()->user()?->can('events.view'), 403);

        $this->record = $this->resolveRecord($record);

        $this->levyRands = ((int) SiteSetting::get(self::LEVY_SETTING_KEY, 0)) / 100;
        $this->keepNonMemberDifference = (bool) SiteSetting::get(self::KEEP_NON_MEMBER_DIFFERENCE_SETTING_KEY, false);
    }

    public static function canAccess(array $parameters = []): bool
    {
        return (bool) auth()->user()?->can('events.view');
    }

    public function getRecord(): Event
    {
        /** @var Event */
        return $this->record;
    }

    protected function report(): MatchDirectorReport
    {
        return new MatchDirectorReport($this->getRecord(), $this->levyCents(), $this->keepNonMemberDifference);
    }

    protected function levyCents(): int
    {
        return (int) round(max(0, $this->levyRands) * 100);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getRows(): Collection
    {
        return $this->report()->rows();
    }

    /**
     * Refunded entries for the slip's refunds section — cancelled registrations
     * that an admin ran through the "Withdraw & refund" row action.
     *
     * @return Collection<int, EventRegistration>
     */
    public function getRefunds(): Collection
    {
        return $this->report()->refunds();
    }

    /**
     * @return array<string, int>
     */
    public function getSummary(): array
    {
        return $this->report()->summary();
    }

    protected function canManagePayments(): bool
    {
        return (bool) auth()->user()?->can('events.registrations.manage');
    }

    protected function canManageAttendance(): bool
    {
        return (bool) auth()->user()?->can('events.attendance.manage');
    }

    public function togglePaid(int $entryId): void
    {
        if (! $this->canManagePayments()) {
            return;
        }

        $entry = $this->entry($entryId);
        if (! $entry) {
            return;
        }

        if ($entry->paid_at !== null) {
            $entry->update(['paid_at' => null, 'marked_paid_by_user_id' => null]);

            return;
        }

        $data = [
            'paid_at' => now(),
            'marked_paid_by_user_id' => auth()->id(),
        ];

        if (in_array($entry->status, [EventRegistrationStatus::Registered, EventRegistrationStatus::Waitlisted], true)) {
            $data['status'] = EventRegistrationStatus::Confirmed;
        }

        $entry->update($data);
    }

    /**
     * Mark an entry paid by a specific method (EFT / cash). Clicking the method
     * an entry is already paid by toggles it back to unpaid.
     */
    public function payVia(int $entryId, string $method): void
    {
        if (! $this->canManagePayments()) {
            return;
        }

        $methodEnum = MatchPaymentMethod::tryFrom($method);
        if ($methodEnum === null) {
            return;
        }

        $entry = $this->entry($entryId);
        if (! $entry) {
            return;
        }

        // Clicking the current method again clears the payment.
        if ($entry->paid_at !== null && $entry->payment_method === $methodEnum) {
            $entry->update([
                'paid_at' => null,
                'payment_method' => null,
                'marked_paid_by_user_id' => null,
            ]);

            return;
        }

        $data = [
            'payment_method' => $methodEnum->value,
            'paid_at' => $entry->paid_at ?? now(),
            'marked_paid_by_user_id' => $entry->marked_paid_by_user_id ?? auth()->id(),
        ];

        if (in_array($entry->status, [EventRegistrationStatus::Registered, EventRegistrationStatus::Waitlisted], true)) {
            $data['status'] = EventRegistrationStatus::Confirmed;
        }

        $entry->update($data);
    }

    public function toggleAttended(int $entryId): void
    {
        if (! $this->canManageAttendance()) {
            return;
        }

        $entry = $this->entry($entryId);
        if (! $entry) {
            return;
        }

        if ($entry->attended) {
            $entry->update(['attended' => false, 'checked_in_at' => null, 'checked_in_by_user_id' => null]);

            return;
        }

        $entry->update([
            'attended' => true,
            'checked_in_at' => now(),
            'checked_in_by_user_id' => auth()->id(),
        ]);
    }

    /**
     * Log a paid no-show's fee to the match-credit ledger so it's tracked as
     * money the club owes them for a future match. Idempotent per entry.
     */
    public function logCredit(int $entryId): void
    {
        if (! $this->canManagePayments()) {
            return;
        }

        $entry = $this->entry($entryId);
        if (! $entry) {
            return;
        }

        $fee = (int) ($entry->effectiveFeeCents() ?? 0);

        if ($entry->paid_at === null || $entry->attended || $fee <= 0) {
            Notification::make()->warning()
                ->title('Not a no-show credit')
                ->body('Only a paid entry that didn\'t shoot can be logged as a credit.')
                ->send();

            return;
        }

        if (MatchCredit::query()->where('source_registration_id', $entry->id)->exists()) {
            Notification::make()->info()
                ->title('Already logged')
                ->body('A credit for this entry is already in the ledger.')
                ->send();

            return;
        }

        $entry->loadMissing('member.user');

        MatchCredit::create([
            'member_id' => $entry->member_id,
            'payee_name' => $entry->shooterName(),
            'payee_email' => $entry->payerEmail(),
            'amount_cents' => $fee,
            'reason' => 'No-show at '.$this->getRecord()->title,
            'source_event_id' => $this->getRecord()->id,
            'source_registration_id' => $entry->id,
            'status' => MatchCreditStatus::Available->value,
            'created_by_user_id' => auth()->id(),
        ]);

        Notification::make()->success()
            ->title('Credit logged')
            ->body($entry->shooterName().'\'s R '.number_format($fee / 100, 2).' credit was added to the ledger.')
            ->send();
    }

    /**
     * Registration ids for this match that already have a ledger credit, so the
     * report can show "credit logged" instead of the button.
     *
     * @return array<int, int>
     */
    public function getLoggedCreditEntryIds(): array
    {
        return MatchCredit::query()
            ->where('source_event_id', $this->getRecord()->id)
            ->whereNotNull('source_registration_id')
            ->pluck('source_registration_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Every extra cost recorded against this match, oldest first, together with
     * a total so the view doesn't have to sum them itself.
     *
     * @return array{items: Collection<int, MatchExpense>, total_cents: int}
     */
    public function getExpenses(): array
    {
        $items = $this->getRecord()->matchExpenses()->with('payeeUser')->get();

        return [
            'items' => $items,
            'total_cents' => (int) $items->sum('amount_cents'),
        ];
    }

    /**
     * Options for the payee-user dropdown on the "Add cost" form. We cap at a
     * sensible limit so the page never ships thousands of <option> rows, and
     * sort by display name so the admin can scan it quickly.
     *
     * @return array<int, string>
     */
    public function getPayeeUserOptions(): array
    {
        return User::query()
            ->whereNotNull('name')
            ->orderBy('name')
            ->limit(500)
            ->pluck('name', 'id')
            ->all();
    }

    /** Enum options for the category <select>, labelled for humans. */
    public function getExpenseCategoryOptions(): array
    {
        return MatchExpenseCategory::options();
    }

    /**
     * Add another line item to the "other costs" list for this match. Only
     * managers of match payments can touch the cost list.
     */
    public function addExpense(): void
    {
        if (! $this->canManagePayments()) {
            return;
        }

        $description = trim($this->newExpenseDescription);
        $freeTextPayee = trim($this->newExpensePayee);
        $amountCents = (int) round(max(0, (float) ($this->newExpenseAmount ?? 0)) * 100);
        $category = MatchExpenseCategory::tryFrom($this->newExpenseCategory) ?? MatchExpenseCategory::Other;

        $payeeUser = null;
        if ($this->newExpensePayeeUserId) {
            $payeeUser = User::query()->whereKey((int) $this->newExpensePayeeUserId)->first();
        }

        if ($description === '') {
            Notification::make()->warning()
                ->title('Add a description')
                ->body('Say what this cost is for so the report reads sensibly.')
                ->send();

            return;
        }

        if ($amountCents <= 0) {
            Notification::make()->warning()
                ->title('Enter an amount')
                ->body('A cost needs a positive rand value.')
                ->send();

            return;
        }

        // When a user is picked, auto-sync the display label so slips never
        // have an empty payee column; keep the typed label as the fallback.
        $payeeLabel = $payeeUser
            ? trim((string) $payeeUser->name)
            : $freeTextPayee;

        $this->getRecord()->matchExpenses()->create([
            'description' => $description,
            'category' => $category->value,
            'payee_name' => $payeeLabel !== '' ? $payeeLabel : null,
            'payee_user_id' => $payeeUser?->id,
            'amount_cents' => $amountCents,
            'created_by_user_id' => auth()->id(),
        ]);

        $this->newExpenseDescription = '';
        $this->newExpensePayee = '';
        $this->newExpenseAmount = null;
        $this->newExpenseCategory = MatchExpenseCategory::Other->value;
        $this->newExpensePayeeUserId = null;

        Notification::make()->success()
            ->title('Cost added')
            ->send();
    }

    /**
     * Remove a cost line. Guarded to this match so a bad id can't wipe
     * something on a neighbouring event.
     */
    public function deleteExpense(int $expenseId): void
    {
        if (! $this->canManagePayments()) {
            return;
        }

        $this->getRecord()->matchExpenses()->whereKey($expenseId)->delete();
    }

    public function saveLevyDefault(): void
    {
        if (! $this->canManagePayments()) {
            return;
        }

        SiteSetting::put(self::LEVY_SETTING_KEY, $this->levyCents(), [
            'group' => 'matches',
            'label' => 'Match director levy per paid shooter (cents)',
        ]);

        SiteSetting::put(self::KEEP_NON_MEMBER_DIFFERENCE_SETTING_KEY, $this->keepNonMemberDifference, [
            'group' => 'matches',
            'label' => 'Club keeps the non-member match-fee difference',
        ]);

        $levy = 'R '.number_format($this->levyCents() / 100, 2).' per paid shooter';
        $difference = $this->keepNonMemberDifference
            ? 'the non-member difference stays with the club'
            : 'the director receives the full guest fee';

        Notification::make()->success()
            ->title('Club defaults saved')
            ->body('New match reports will start with '.$levy.'; '.$difference.'.')
            ->send();
    }

    protected function entry(int $entryId): ?EventRegistration
    {
        return $this->getRecord()->registrations()->whereKey($entryId)->first();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_walkin')
                ->label('Add walk-in')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->visible(fn () => $this->canManagePayments())
                ->modalHeading('Add a walk-in shooter')
                ->modalDescription('Add someone who entered on the day. Record how they paid — leave as "Not paid yet" if they still owe.')
                ->modalSubmitActionLabel('Add to match')
                ->schema([
                    Select::make('member_id')
                        ->label('Member')
                        ->options(fn () => Member::query()
                            ->orderBy('last_name')
                            ->orderBy('first_name')
                            ->limit(1000)
                            ->get()
                            ->mapWithKeys(fn (Member $m) => [
                                $m->id => trim(($m->first_name ?? '').' '.($m->last_name ?? ''))
                                    .($m->membership_number ? " ({$m->membership_number})" : ''),
                            ])
                            ->all())
                        ->searchable()
                        ->helperText('Leave blank and use the guest name for a non-member.'),
                    TextInput::make('guest_name')
                        ->label('Guest name')
                        ->maxLength(150),
                    Select::make('division')
                        ->label('Division')
                        ->options(fn () => collect($this->getRecord()->registrationDivisionChoices())
                            ->mapWithKeys(fn (string $v) => [$v => $v])->all())
                        ->searchable(),
                    Select::make('category')
                        ->label('Category')
                        ->options(fn () => collect($this->getRecord()->registrationCategoryChoices())
                            ->mapWithKeys(fn (string $v) => [$v => $v])->all())
                        ->searchable(),
                    Toggle::make('is_junior')
                        ->label('Junior (junior pricing)')
                        ->inline(false),
                    Select::make('pay')
                        ->label('Payment')
                        ->options([
                            '' => 'Not paid yet',
                            MatchPaymentMethod::Cash->value => 'Paid — cash',
                            MatchPaymentMethod::Eft->value => 'Paid — EFT',
                        ])
                        ->default(MatchPaymentMethod::Cash->value),
                ])
                ->action(function (array $data): void {
                    $memberId = $data['member_id'] ?? null;
                    $guestName = trim((string) ($data['guest_name'] ?? ''));

                    if (! $memberId && $guestName === '') {
                        Notification::make()->warning()
                            ->title('Pick a member or enter a guest name')
                            ->send();

                        return;
                    }

                    $method = MatchPaymentMethod::tryFrom((string) ($data['pay'] ?? ''));

                    $attributes = [
                        'member_id' => $memberId ?: null,
                        'guest_name' => $guestName ?: null,
                        'division' => $data['division'] ?? null,
                        'category' => $data['category'] ?? null,
                        'is_junior' => (bool) ($data['is_junior'] ?? false),
                        'status' => EventRegistrationStatus::Registered->value,
                        'attended' => true,
                        'checked_in_at' => now(),
                        'checked_in_by_user_id' => auth()->id(),
                        'registered_at' => now(),
                    ];

                    if ($method !== null) {
                        $attributes['paid_at'] = now();
                        $attributes['payment_method'] = $method->value;
                        $attributes['marked_paid_by_user_id'] = auth()->id();
                        $attributes['status'] = EventRegistrationStatus::Confirmed->value;
                    }

                    $this->getRecord()->registrations()->create($attributes);

                    Notification::make()->success()
                        ->title('Walk-in added')
                        ->send();
                }),
            Action::make('back')
                ->label('Back to match')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => EventResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'Report';
    }

    public function getTitle(): string
    {
        return 'Match report — '.$this->getRecord()->title;
    }

    public function canManagePaymentsPublic(): bool
    {
        return $this->canManagePayments();
    }

    public function canManageAttendancePublic(): bool
    {
        return $this->canManageAttendance();
    }
}
