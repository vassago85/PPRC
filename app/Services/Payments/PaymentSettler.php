<?php

namespace App\Services\Payments;

use App\Enums\EventRegistrationStatus;
use App\Enums\MatchPaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\EventRegistration;
use App\Models\MembershipPayment;
use App\Models\User;
use App\Services\Events\MatchEntryPaymentRequestService;
use App\Services\Membership\MemberService;
use Illuminate\Auth\Access\AuthorizationException;
use RuntimeException;

/**
 * Records money against whatever a reconciliation screen decided it belongs to.
 *
 * Both the single-line lookup and the statement upload settle payments, and they
 * have to do it identically — same permission checks, same EFT payment method,
 * same confirmation emails — so the behaviour lives here rather than in either
 * page.
 */
class PaymentSettler
{
    /**
     * Settle whatever a "kind:id" key points at, returning a sentence
     * describing what happened.
     *
     * @throws AuthorizationException when the actor may not settle that kind
     * @throws RuntimeException when there is nothing left to settle
     */
    public function settle(string $key, User $actor): string
    {
        [$kind, $id] = array_pad(explode(':', $key, 2), 2, null);

        return match ($kind) {
            PaymentMatch::MATCH_ENTRY => $this->settleMatchEntry((int) $id, $actor),
            PaymentMatch::MEMBERSHIP_PAYMENT => $this->settleMembershipPayment((int) $id, $actor),
            default => throw new RuntimeException('That is not something this can settle from here.'),
        };
    }

    public function canSettle(string $kind, ?User $actor): bool
    {
        return match ($kind) {
            PaymentMatch::MATCH_ENTRY => (bool) $actor?->can('events.registrations.manage'),
            PaymentMatch::MEMBERSHIP_PAYMENT => (bool) $actor?->can('payments.eft.confirm'),
            default => false,
        };
    }

    /**
     * Money that arrived in the club account is an EFT by definition, and
     * recording it that way is what keeps the match director's report splitting
     * bank and cash correctly.
     */
    protected function settleMatchEntry(int $id, User $actor): string
    {
        if (! $this->canSettle(PaymentMatch::MATCH_ENTRY, $actor)) {
            throw new AuthorizationException('You may not settle match entries.');
        }

        $entry = EventRegistration::query()->with(['event', 'member.user'])->find($id);

        if (! $entry) {
            throw new RuntimeException('That entry no longer exists.');
        }

        if ($entry->paid_at !== null || ! $entry->awaitingPayment()) {
            throw new RuntimeException(
                $entry->shooterName().'\'s entry is already settled, or has nothing outstanding.',
            );
        }

        $attributes = [
            'paid_at' => now(),
            'payment_method' => MatchPaymentMethod::Eft->value,
            'marked_paid_by_user_id' => $actor->id,
        ];

        if (in_array($entry->status, [EventRegistrationStatus::Registered, EventRegistrationStatus::Waitlisted], true)) {
            $attributes['status'] = EventRegistrationStatus::Confirmed;
        }

        $entry->update($attributes);

        $emailed = app(MatchEntryPaymentRequestService::class)->sendConfirmation($entry);

        return $entry->shooterName().'\'s entry for '.($entry->event?->title ?? 'the match').' is settled.'
            .($emailed ? ' A confirmation email was sent.' : '');
    }

    protected function settleMembershipPayment(int $id, User $actor): string
    {
        if (! $this->canSettle(PaymentMatch::MEMBERSHIP_PAYMENT, $actor)) {
            throw new AuthorizationException('You may not confirm membership payments.');
        }

        $payment = MembershipPayment::query()->with('membership')->find($id);

        if (! $payment) {
            throw new RuntimeException('That payment no longer exists.');
        }

        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Submitted], true)) {
            throw new RuntimeException('That payment is no longer awaiting confirmation.');
        }

        if (! $payment->membership) {
            throw new RuntimeException(
                'That payment has no membership attached — sort the link out on the payments list first.',
            );
        }

        // Single source of truth: this confirms the payment, activates the
        // membership and sends the activation email.
        app(MemberService::class)->activate($payment->membership, $actor);

        // Activation is a no-op on a membership that is already active, which
        // would quietly leave the deposit unrecorded. That happens when someone
        // pays twice, or pays late enough that an admin activated them by hand.
        if ($payment->refresh()->status !== PaymentStatus::Confirmed) {
            $payment->update([
                'status' => PaymentStatus::Confirmed,
                'confirmed_at' => now(),
                'confirmed_by_user_id' => $actor->id,
            ]);

            return 'Payment recorded. The membership was already active, so only the payment needed confirming.';
        }

        $who = $payment->payerName();

        return 'Payment confirmed and '.($who !== '—' ? $who.'\'s' : 'the').' membership activated.';
    }
}
