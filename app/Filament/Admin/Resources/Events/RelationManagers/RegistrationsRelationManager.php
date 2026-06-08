<?php

namespace App\Filament\Admin\Resources\Events\RelationManagers;

use App\Enums\EventRegistrationStatus;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Services\Events\MatchEntryPaymentRequestService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'Entries';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('member_id')
                    ->label('Member')
                    ->options(fn () => Member::query()
                        ->with('user.roles')
                        ->orderBy('last_name')
                        ->orderBy('first_name')
                        ->limit(500)
                        ->get()
                        ->mapWithKeys(function ($m) {
                            $base = trim(($m->first_name ?? '').' '.($m->last_name ?? ''))
                                .($m->membership_number ? " ({$m->membership_number})" : '');
                            $label = $m->user?->hasFreeEventEntry()
                                ? $base.'  — ExCo · free entry'
                                : $base;

                            return [$m->id => $label];
                        })
                        ->all())
                    ->searchable()
                    ->preload()
                    ->helperText('Leave blank and fill guest details below for non-members. ExCo / committee members are auto-waived.'),

                TextInput::make('guest_name')->maxLength(150),
                TextInput::make('guest_email')->email()->maxLength(150),
                TextInput::make('guest_phone')->tel()->maxLength(32),

                Select::make('division')
                    ->label('Division')
                    ->options(fn () => collect($this->getOwnerRecord()->registrationDivisionChoices())
                        ->mapWithKeys(fn (string $v) => [$v => $v])
                        ->all())
                    ->searchable()
                    ->nullable(),
                Select::make('category')
                    ->label('Category')
                    ->options(fn () => collect($this->getOwnerRecord()->registrationCategoryChoices())
                        ->mapWithKeys(fn (string $v) => [$v => $v])
                        ->all())
                    ->searchable()
                    ->nullable(),

                Select::make('course')
                    ->label('Course')
                    ->options([
                        'full' => 'Full course (provincial / SAPRF)',
                        'club' => 'Club course (PPRC short)',
                    ])
                    ->visible(fn () => $this->getOwnerRecord()->offersBothCourses())
                    ->helperText('Which course of fire this shooter is doing — only relevant on combined matches.'),

                Select::make('status')
                    ->options(collect(EventRegistrationStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                    ->default(EventRegistrationStatus::Registered->value)
                    ->required(),

                TextInput::make('squad_number')->numeric(),
                TextInput::make('firing_order')->numeric(),

                Toggle::make('is_saprf_entry')
                    ->label('SAPRF entry')
                    ->helperText('Shooter pays through the SAPRF website. PPRC doesn\'t charge them.')
                    ->inline(false),

                Toggle::make('is_junior')
                    ->label('Junior shooter (under 18)')
                    ->helperText('Applies the junior fee tier. Members under 18 are detected automatically — only flag this for guests or to override.')
                    ->inline(false),

                Toggle::make('free_entry')
                    ->label('Free entry (comped)')
                    ->helperText('Waive the fee — this shooter shoots for free. Use for guests, officials or sponsors the match director comps.')
                    ->live()
                    ->dehydrated(false)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('fee_cents', $state ? 0 : null))
                    ->inline(false),

                TextInput::make('fee_cents')
                    ->label('Fee override (ZAR)')
                    ->numeric()
                    ->prefix('R')
                    ->helperText('Leave blank to use the match\'s member / non-member price. Set to 0 (or toggle "Free entry") to waive. ExCo members and SAPRF entries are handled automatically.')
                    ->dehydrateStateUsing(fn ($state) => $state === null || $state === ''
                        ? null
                        : (int) round(((float) $state) * 100))
                    ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100),

                Toggle::make('attended')->inline(false),

                Textarea::make('notes')->rows(2)->columnSpanFull(),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy('squad_number')
                ->orderBy('firing_order'))
            ->columns([
                TextColumn::make('squad_number')->label('Squad')->sortable(),
                TextColumn::make('firing_order')->label('Order')->sortable(),
                TextColumn::make('shooter_display')
                    ->label('Shooter')
                    ->state(fn (EventRegistration $r) => $r->shooterName())
                    ->searchable(
                        query: fn ($query, string $search) => $query
                            ->where('guest_name', 'like', "%{$search}%")
                            ->orWhereHas('member', fn ($q) => $q
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")),
                    ),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?EventRegistrationStatus $state) => $state?->label())
                    ->color(fn (?EventRegistrationStatus $state) => $state?->color() ?? 'gray'),
                TextColumn::make('fee_display')
                    ->label('Fee')
                    ->state(function (EventRegistration $r) {
                        if ($r->is_saprf_entry) {
                            return 'Paid via SAPRF';
                        }
                        if ($r->isWaived()) {
                            return 'Waived';
                        }
                        $cents = $r->effectiveFeeCents();
                        if ($cents === null) {
                            return '—';
                        }

                        return 'R '.number_format($cents / 100, 2);
                    })
                    ->badge()
                    ->color(fn (EventRegistration $r) => match (true) {
                        $r->is_saprf_entry => 'info',
                        $r->isWaived() => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->state(fn (EventRegistration $r) => match (true) {
                        $r->paid_at !== null => 'Paid',
                        $r->awaitingPayment() => 'Awaiting',
                        default => 'No fee',
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Paid' => 'success',
                        'Awaiting' => 'warning',
                        default => 'gray',
                    })
                    ->description(fn (EventRegistration $r) => $r->paid_at?->format('d M Y')),
                TextColumn::make('division')->label('Div.')->toggleable(),
                TextColumn::make('category')->label('Cat.')->toggleable(),
                TextColumn::make('course')
                    ->label('Course')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'full' => 'Full',
                        'club' => 'Club',
                        default => null,
                    })
                    ->badge()
                    ->color(fn (?string $state) => $state === 'club' ? 'info' : 'primary')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_saprf_entry')->boolean()->label('SAPRF')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_junior')->boolean()->label('Junior')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('attended')->boolean()->label('Attended')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('checked_in_at')->dateTime('d M H:i')->label('Checked in')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(EventRegistrationStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
                TernaryFilter::make('paid')
                    ->label('Payment')
                    ->placeholder('All entries')
                    ->trueLabel('Marked paid')
                    ->falseLabel('Not marked paid')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('paid_at'),
                        false: fn (Builder $query) => $query->whereNull('paid_at'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn () => auth()->user()?->can('events.registrations.manage'))
                    ->mutateDataUsing(fn (array $data) => array_merge($data, [
                        'registered_at' => now(),
                    ])),
            ])
            ->recordActions([
                Action::make('send_payment_email')
                    ->label('Send payment email')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send payment email')
                    ->modalDescription(fn (EventRegistration $r) => 'Email '.$r->shooterName().' at '
                        .($r->payerEmail() ?? '—').' with the entry fee (R '
                        .number_format((int) ($r->effectiveFeeCents() ?? 0) / 100, 2)
                        .'), banking details and a payment reference?')
                    ->visible(fn (EventRegistration $r) => $r->owesPayment()
                        && auth()->user()?->can('events.registrations.manage'))
                    ->action(function (EventRegistration $r) {
                        try {
                            app(MatchEntryPaymentRequestService::class)->send($r);

                            Notification::make()->success()
                                ->title('Payment email sent')
                                ->body('Sent to '.$r->payerEmail().' with reference '.$r->paymentReference().'.')
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()->danger()
                                ->title('Could not send payment email')
                                ->body(collect($e->errors())->flatten()->first() ?? $e->getMessage())
                                ->send();
                        }
                    }),
                Action::make('mark_paid')
                    ->label('Mark paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm payment received')
                    ->modalDescription(fn (EventRegistration $r) => 'Mark '.$r->shooterName().'\'s entry (R '
                        .number_format((int) ($r->effectiveFeeCents() ?? 0) / 100, 2)
                        .') as paid? Do this once the EFT reflects in the club account.')
                    ->visible(fn (EventRegistration $r) => $r->paid_at === null
                        && $r->awaitingPayment()
                        && auth()->user()?->can('events.registrations.manage'))
                    ->action(function (EventRegistration $r) {
                        $r->update($this->paidAttributes($r));

                        Notification::make()->success()
                            ->title('Marked as paid')
                            ->body($r->shooterName().'\'s entry fee is confirmed received.')
                            ->send();
                    }),
                Action::make('mark_unpaid')
                    ->label('Undo paid')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Undo payment confirmation')
                    ->visible(fn (EventRegistration $r) => $r->paid_at !== null
                        && auth()->user()?->can('events.registrations.manage'))
                    ->action(function (EventRegistration $r) {
                        $r->update([
                            'paid_at' => null,
                            'marked_paid_by_user_id' => null,
                        ]);

                        Notification::make()->success()
                            ->title('Marked as unpaid')
                            ->send();
                    }),
                Action::make('check_in')
                    ->label('Check in')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (EventRegistration $r) => ! $r->attended
                        && auth()->user()?->can('events.attendance.manage'))
                    ->action(function (EventRegistration $r) {
                        $r->update([
                            'attended' => true,
                            'checked_in_at' => now(),
                            'checked_in_by_user_id' => auth()->id(),
                        ]);
                    }),
                EditAction::make()
                    ->visible(fn () => auth()->user()?->can('events.registrations.manage')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()?->can('events.registrations.manage')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('send_payment_email')
                        ->label('Send payment email')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Send payment emails')
                        ->modalDescription('Email the selected entries their banking details and payment reference. Entries that owe nothing (free / ExCo / SAPRF) or have no email are skipped automatically.')
                        ->visible(fn () => auth()->user()?->can('events.registrations.manage'))
                        ->action(function (Collection $records) {
                            $result = app(MatchEntryPaymentRequestService::class)->sendBulk($records);

                            Notification::make()->success()
                                ->title('Payment emails sent')
                                ->body("Sent {$result['sent']}, skipped {$result['skipped']} (nothing owed or no email).")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('mark_paid')
                        ->label('Mark paid')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirm payments received')
                        ->modalDescription('Mark the selected entries as paid. Entries that owe nothing (free / ExCo / SAPRF) or are already paid are skipped automatically.')
                        ->visible(fn () => auth()->user()?->can('events.registrations.manage'))
                        ->action(function (Collection $records) {
                            $count = 0;

                            foreach ($records as $r) {
                                if ($r->paid_at !== null || ! $r->awaitingPayment()) {
                                    continue;
                                }

                                $r->update($this->paidAttributes($r));
                                $count++;
                            }

                            Notification::make()->success()
                                ->title('Marked as paid')
                                ->body($count.' '.str('entry')->plural($count).' updated.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('events.registrations.manage')),
                ]),
            ]);
    }

    /**
     * Attributes applied when confirming an entry's fee was received. Marking
     * paid also confirms the entry (the "approval"), but never overrides a
     * cancelled / no-show status.
     *
     * @return array<string, mixed>
     */
    protected function paidAttributes(EventRegistration $r): array
    {
        $data = [
            'paid_at' => now(),
            'marked_paid_by_user_id' => auth()->id(),
        ];

        if (in_array($r->status, [EventRegistrationStatus::Registered, EventRegistrationStatus::Waitlisted], true)) {
            $data['status'] = EventRegistrationStatus::Confirmed;
        }

        return $data;
    }
}
