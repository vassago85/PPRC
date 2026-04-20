<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Delivered to the site's contact email (SiteSetting `contact.email`)
 * whenever a visitor submits /contact.
 *
 * We send FROM the club's configured "from" address so SPF/DKIM still aligns,
 * and set REPLY-TO to the visitor so the committee can just hit reply.
 */
class ContactFormMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public ?string $senderSubject,
        public string $messageBody,
        public ?string $ipAddress = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->senderSubject
            ? 'PPRC website — '.$this->senderSubject
            : 'PPRC website — new enquiry from '.$this->senderName;

        return new Envelope(
            subject: $subject,
            replyTo: [new Address($this->senderEmail, $this->senderName)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
            with: [
                'senderName'    => $this->senderName,
                'senderEmail'   => $this->senderEmail,
                'senderSubject' => $this->senderSubject,
                'messageBody'   => $this->messageBody,
                'ipAddress'     => $this->ipAddress,
            ],
        );
    }
}
