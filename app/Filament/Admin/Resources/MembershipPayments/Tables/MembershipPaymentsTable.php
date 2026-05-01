<?php

namespace App\Filament\Admin\Resources\MembershipPayments\Tables;

use App\Enums\MembershipStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\MembershipPayment;
use App\Services\Membership\MemberService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class MembershipPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Payment ref')
                    ->copyable()
                    ->copyMessage('Reference copied')
                    ->fontFamily('mono')
                    ->weight('semibold')
                    ->searchable()
                    ->placeholder('—')
                    ->description(fn (MembershipPayment $r) => $r->paystack_reference
                        ? 'Paystack: '.$r->paystack_reference
                        : null),

                TextColumn::make('membership.member.membership_number')
                    ->label('Member #')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('membership.member.full_name')
                    ->label('Member')
                    ->state(fn (MembershipPayment $r) => $r->membership?->member?->fullName() ?? '—')
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereHas('membership.member', fn ($q) => $q
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email_cached', 'like', "%{$search}%"));
                    })
                    ->description(fn (MembershipPayment $r) => $r->membership?->membershipType?->name
                        ?? $r->membership?->membership_type_name_snapshot),

                TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(fn (?int $state) => $state !== null
                        ? 'R '.number_format($state / 100, 2)
                        : '—')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('provider')
                    ->label('Method')
                    ->formatStateUsing(fn (?PaymentProvider $state) => match ($state) {
                        PaymentProvider::ManualEft => 'EFT',
                        PaymentProvider::Paystack => 'Paystack',
                        default => '—',
                    })
                    ->badge()
                    ->color(fn (?PaymentProvider $state) => $state === PaymentProvider::Paystack ? 'info' : 'gray'),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?PaymentStatus $state) => $state?->label())
                    ->color(fn (?PaymentStatus $state) => $state?->color() ?? 'gray')
                    ->sortable(),

                TextColumn::make('membership.status')
                    ->label('Membership')
                    ->badge()
                    ->formatStateUsing(fn (?MembershipStatus $state) => $state?->label())
                    ->color(fn (?MembershipStatus $state) => $state?->color() ?? 'gray')
                    ->toggleable(),

                TextColumn::make('submitted_at')
                    ->dateTime('d M Y H:i')
                    ->label('Submitted')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('confirmed_at')
                    ->dateTime('d M Y H:i')
                    ->label('Confirmed')
                    ->sortable()
                    ->placeholder('—')
                    ->description(fn (MembershipPayment $r) => $r->confirmedBy?->name)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->label('Created')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(PaymentStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                        ->all())
                    ->multiple()
                    ->default([PaymentStatus::Submitted->value, PaymentStatus::Pending->value]),

                SelectFilter::make('provider')
                    ->options(collect(PaymentProvider::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => match ($c) {
                            PaymentProvider::ManualEft => 'EFT',
                            PaymentProvider::Paystack => 'Paystack',
                            default => $c->value,
                        }])->all()),

                TernaryFilter::make('has_proof')
                    ->label('Proof uploaded')
                    ->placeholder('All')
                    ->trueLabel('Has proof')
                    ->falseLabel('No proof yet')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('proof_path'),
                        false: fn (Builder $query) => $query->whereNull('proof_path'),
                    ),

                Filter::make('confirmed_this_month')
                    ->label('Confirmed this month')
                    ->query(fn (Builder $query) => $query
                        ->where('status', PaymentStatus::Confirmed->value)
                        ->where('confirmed_at', '>=', now()->startOfMonth())),
            ])
            ->recordActions([
                Action::make('viewProof')
                    ->label('Proof')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('info')
                    ->visible(fn (MembershipPayment $r) => filled($r->proof_path))
                    ->url(fn (MembershipPayment $r) => self::proofUrl($r), shouldOpenInNewTab: true),

                Action::make('confirm')
                    ->label('Confirm + activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (MembershipPayment $r) => auth()->user()?->can('payments.eft.confirm')
                        && in_array($r->status, [PaymentStatus::Pending, PaymentStatus::Submitted], true))
                    ->requiresConfirmation()
                    ->modalHeading('Confirm payment received')
                    ->modalDescription(fn (MembershipPayment $r) => 'This will mark this payment as Confirmed and activate the membership for '
                        .($r->membership?->member?->fullName() ?? 'this member')
                        .'. The member will receive an activation email.')
                    ->modalSubmitActionLabel('Confirm + activate')
                    ->action(function (MembershipPayment $r) {
                        if (! $r->membership) {
                            Notification::make()->danger()
                                ->title('Payment has no membership attached')
                                ->send();
                            return;
                        }

                        // Single source of truth: MemberService::activate
                        // confirms the payment AND activates the membership
                        // (sends the approval email and cleans up superseded
                        // pending memberships).
                        app(MemberService::class)->activate($r->membership, auth()->user());

                        Notification::make()->success()
                            ->title('Payment confirmed and membership activated')
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (MembershipPayment $r) => auth()->user()?->can('payments.eft.confirm')
                        && in_array($r->status, [PaymentStatus::Pending, PaymentStatus::Submitted], true))
                    ->schema([
                        Textarea::make('reason')
                            ->label('Reason (visible in admin notes)')
                            ->rows(3)
                            ->placeholder('e.g. Amount short by R200, asked member to top up.'),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Reject this payment?')
                    ->modalDescription('The payment will be marked as Failed. The membership status is left untouched so the member can re-submit.')
                    ->action(function (MembershipPayment $r, array $data) {
                        $meta = $r->meta?->toArray() ?? [];
                        $meta['rejections'] = $meta['rejections'] ?? [];
                        $meta['rejections'][] = [
                            'reason' => $data['reason'] ?? null,
                            'by_user_id' => auth()->id(),
                            'at' => now()->toIso8601String(),
                        ];

                        $r->update([
                            'status' => PaymentStatus::Failed->value,
                            'meta' => $meta,
                        ]);

                        Notification::make()->warning()
                            ->title('Payment rejected')
                            ->body('Status set to Failed. The member can re-upload proof from their portal.')
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkConfirm')
                        ->label('Confirm + activate selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn () => (bool) auth()->user()?->can('payments.eft.confirm'))
                        ->requiresConfirmation()
                        ->modalDescription('Each selected payment will be marked Confirmed and its membership activated.')
                        ->action(function (Collection $records) {
                            $confirmed = 0;
                            $skipped = 0;
                            foreach ($records as $payment) {
                                if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Submitted], true)) {
                                    $skipped++;
                                    continue;
                                }
                                if (! $payment->membership) {
                                    $skipped++;
                                    continue;
                                }
                                app(MemberService::class)->activate($payment->membership, auth()->user());
                                $confirmed++;
                            }

                            Notification::make()->success()
                                ->title("Confirmed {$confirmed} payment(s)".($skipped ? ", skipped {$skipped}" : ''))
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    private static function proofUrl(MembershipPayment $payment): ?string
    {
        if (! $payment->proof_path) {
            return null;
        }

        $disk = Storage::disk(\App\Support\MediaDisk::name());

        try {
            // Temporary URL works on S3/R2; falls back gracefully on local.
            return method_exists($disk, 'temporaryUrl')
                ? $disk->temporaryUrl($payment->proof_path, now()->addMinutes(15))
                : $disk->url($payment->proof_path);
        } catch (\Throwable) {
            return $disk->url($payment->proof_path);
        }
    }
}
