<?php

namespace App\Filament\Admin\Support;

use App\Models\EventRegistration;
use App\Services\Events\MatchEntryPaymentRequestService;

class MatchCreditSettlement
{
    /**
     * Tell the shooter where their credit landed and hand the admin a one-line
     * summary for the toast.
     *
     * Which email they get depends on whether the credit stretched far enough:
     * a fully covered entry is confirmed, a short one gets a payment request so
     * they are chased for the difference and nothing else.
     */
    public static function announce(EventRegistration $entry): string
    {
        $service = app(MatchEntryPaymentRequestService::class);
        $outstanding = $entry->outstandingCents();
        $applied = 'R '.number_format($entry->creditAppliedCents() / 100, 2).' credit applied.';

        if ($outstanding <= 0) {
            $emailed = $service->sendConfirmation($entry);

            return $applied.' Entry is paid in full.'
                .($emailed ? ' They have been emailed a confirmation.' : '');
        }

        $shortfall = 'R '.number_format($outstanding / 100, 2).' still to pay.';

        try {
            $service->send($entry);

            return $applied.' '.$shortfall.' We have emailed them the difference and a reference.';
        } catch (\Throwable) {
            return $applied.' '.$shortfall.' We could not email them — reference '
                .$entry->paymentReference().'.';
        }
    }
}
