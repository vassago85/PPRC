<?php

use App\Listeners\LogSentEmail;
use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage as LaravelSentMessage;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

function fakeSentMessage(): MessageSent
{
    $email = (new Email())
        ->from('club@pretoriaprc.co.za')
        ->to('shooter@example.com')
        ->subject('Payment details for Test Match')
        ->html('<p>Pay up</p>');

    $sent = new LaravelSentMessage(new SymfonySentMessage(
        $email,
        new Envelope(new Address('club@pretoriaprc.co.za'), [new Address('shooter@example.com')]),
    ));

    return new MessageSent($sent, ['__laravel_mailable' => 'App\\Mail\\MatchEntryPaymentMail']);
}

it('logs only one row when MessageSent fires twice for one send', function () {
    $listener = new LogSentEmail();
    $event = fakeSentMessage();

    $listener->handle($event);
    $listener->handle($event);

    expect(EmailLog::where('to_email', 'shooter@example.com')->count())->toBe(1);
});

it('still logs distinct emails to the same address', function () {
    $listener = new LogSentEmail();

    $listener->handle(fakeSentMessage());

    $other = fakeSentMessage();
    $other->sent->getOriginalMessage()->subject('Payment received for Test Match');
    $listener->handle($other);

    expect(EmailLog::where('to_email', 'shooter@example.com')->count())->toBe(2);
});
