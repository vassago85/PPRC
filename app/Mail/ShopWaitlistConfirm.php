<?php

namespace App\Mail;

use App\Models\ShopWaitlistSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShopWaitlistConfirm extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShopWaitlistSubscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your PPRC shop waitlist signup',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shop.waitlist-confirm-html',
            with: [
                'subscriber' => $this->subscriber,
                'confirmUrl' => route('shop.waitlist.confirm', ['token' => $this->subscriber->confirm_token]),
            ],
        );
    }
}
