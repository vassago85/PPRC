<?php

namespace App\Filament\Admin\Actions;

use App\Enums\MembershipStatus;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Services\Membership\MembershipPaymentRequestService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class ResendMembershipPaymentRequestAction
{
    /*
    |--------------------------------------------------------------------------
    | Why every closure below is nullable
    |--------------------------------------------------------------------------
    |
    | Filament calls the visibility closure during page render to decide
    | whether the button, dropdown item, or group entry is even drawn. On a
    | custom page (like ViewMember) the header-action pipeline can evaluate
    | that closure *without* a bound record — the same closure is also asked
    | "would you be hidden in a group?" as part of the button HTML build.
    | A non-nullable type-hint blows the page up with a TypeError at render
    | time instead of just hiding the action, and there is no other useful
    | answer than "hide me" when the caller has no record to give.
    |
    | So every closure accepts `?Model $record = null` and short-circuits.
    | The action closures are guarded too, defensively — an action fired
    | without a record shouldn't be reachable anyway (visibility would have
    | returned false), but a stack trace beats a silent send.
    |
    */

    public static function forMembership(): Action
    {
        return Action::make('resend_payment_request')
            ->label('Resend payment request')
            ->icon('heroicon-o-envelope')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Resend payment request')
            ->modalDescription(fn (?Membership $record = null) => self::modalDescription($record))
            ->visible(fn (?Membership $record = null) => $record !== null
                && self::canSendForMembership($record))
            ->action(function (?Membership $record = null): void {
                if ($record === null) {
                    return;
                }

                self::send($record);
            });
    }

    public static function forPayment(): Action
    {
        return Action::make('resend_payment_request')
            ->label('Resend payment request')
            ->icon('heroicon-o-envelope')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Resend payment request')
            ->modalDescription(fn (?MembershipPayment $record = null) => self::modalDescription($record?->membership))
            ->visible(fn (?MembershipPayment $record = null) => $record !== null
                && self::canSendForPayment($record))
            ->action(function (?MembershipPayment $record = null): void {
                if (! $record?->membership) {
                    return;
                }

                self::send($record->membership);
            });
    }

    public static function forMember(): Action
    {
        return Action::make('resend_payment_request')
            ->label('Resend payment request')
            ->icon('heroicon-o-envelope')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Resend payment request')
            ->modalDescription(fn (?Member $record = null) => self::modalDescription($record?->currentMembership()))
            ->visible(fn (?Member $record = null) => $record !== null
                && self::canSendForMembership($record->currentMembership()))
            ->action(function (?Member $record = null): void {
                $membership = $record?->currentMembership();
                if (! $membership) {
                    return;
                }

                self::send($membership);
            });
    }

    public static function canSendForPayment(MembershipPayment $payment): bool
    {
        return $payment->status === PaymentStatus::Pending
            && self::canSendForMembership($payment->membership);
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
