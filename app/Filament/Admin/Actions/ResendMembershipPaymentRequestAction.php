<?php

namespace App\Filament\Admin\Actions;

use App\Enums\MembershipStatus;
use App\Models\Member;
use App\Models\Membership;
use App\Services\Membership\MembershipPaymentRequestService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class ResendMembershipPaymentRequestAction
{
    public static function forMembership(): Action
    {
        return Action::make('resend_payment_request')
            ->label('Resend payment request')
            ->icon('heroicon-o-envelope')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Resend payment request')
            ->modalDescription(fn (Membership $record) => self::modalDescription($record))
            ->visible(fn (Membership $record) => self::canSendForMembership($record))
            ->action(fn (Membership $record) => self::send($record));
    }

    public static function forMember(): Action
    {
        return Action::make('resend_payment_request')
            ->label('Resend payment request')
            ->icon('heroicon-o-envelope')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Resend payment request')
            ->modalDescription(fn (Member $record) => self::modalDescription($record->currentMembership()))
            ->visible(fn (Member $record) => self::canSendForMembership($record->currentMembership()))
            ->action(function (Member $record): void {
                $membership = $record->currentMembership();
                if (! $membership) {
                    return;
                }

                self::send($membership);
            });
    }

    public static function canSendForMembership(?Membership $membership): bool
    {
        if (! auth()->user()?->can('memberships.manage')) {
            return false;
        }

        if (! $membership || $membership->status !== MembershipStatus::PendingPayment) {
            return false;
        }

        return filled($membership->member?->user?->email);
    }

    private static function modalDescription(?Membership $membership): string
    {
        if (! $membership) {
            return 'No pending membership found.';
        }

        $email = $membership->member?->user?->email ?? '—';
        $name = $membership->member?->fullName() ?? 'Member';

        return "Email {$name} at {$email} with banking details, their payment reference, and a link to upload proof of payment if they have already paid?";
    }

    private static function send(Membership $membership): void
    {
        try {
            $payment = app(MembershipPaymentRequestService::class)->send($membership);

            Notification::make()->success()
                ->title('Payment request sent')
                ->body("Email sent with reference {$payment->reference}.")
                ->send();
        } catch (ValidationException $e) {
            Notification::make()->danger()
                ->title('Could not send payment request')
                ->body(collect($e->errors())->flatten()->first() ?? $e->getMessage())
                ->send();
        }
    }
}
