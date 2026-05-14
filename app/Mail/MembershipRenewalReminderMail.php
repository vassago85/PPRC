<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Reminder mail for members whose membership is nearly expired or has
 * already lapsed. Includes both a "Renew" CTA pointing at their portal
 * and a signed self-service "Cancel my membership" link so we never
 * lock anyone in — they can opt out with two clicks.
 */
class MembershipRenewalReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $variant 'expiring' or 'lapsed'
     * @param int    $days    Days until expiry (positive) or days lapsed (positive int)
     */
    public function __construct(
        public Member $member,
        public string $variant,
        public int $days,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->variant) {
            'lapsed' => 'Your PPRC membership has lapsed — renew when you\'re ready',
            default  => 'Your PPRC membership is about to expire',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.membership-renewal-reminder',
            with: [
                'member'      => $this->member,
                'variant'     => $this->variant,
                'days'        => $this->days,
                'expiryDate'  => $this->member->expiry_date,
                'renewUrl'    => url('/portal/membership?via=reminder'),
                'cancelUrl'   => $this->cancelUrl(),
            ],
        );
    }

    /**
     * 30-day signed link to the cancellation confirmation page.
     * Long-ish window so members can sit on the email and decide.
     */
    private function cancelUrl(): string
    {
        return URL::temporarySignedRoute(
            'membership.cancel.confirm',
            now()->addDays(30),
            ['member' => $this->member->id],
        );
    }
}
