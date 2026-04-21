<?php

namespace App\Mail;

use App\Models\ShopRun;
use App\Models\ShopWaitlistSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShopRunOpened extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShopRun $run,
        public ShopWaitlistSubscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PPRC apparel orders are open: '.$this->run->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shop.run-opened',
            with: [
                'run' => $this->run,
                'subscriber' => $this->subscriber,
                'shopUrl' => route('shop.run', $this->run),
                'portalUrl' => route('portal.shop.run', $this->run),
                'unsubscribeUrl' => route('shop.waitlist.unsubscribe', ['token' => $this->subscriber->unsubscribe_token]),
            ],
        );
    }
}
