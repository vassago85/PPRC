<?php

use App\Support\EmailBodyExtractor;
use Symfony\Component\Mime\Email;

it('extracts html bodies from sent messages', function () {
    $message = (new Email)
        ->html('<html><body><p>Hello member</p></body></html>');

    expect(EmailBodyExtractor::fromMessage($message))
        ->toBe('<html><body><p>Hello member</p></body></html>');
});

it('wraps plain text bodies in a minimal html document', function () {
    $message = (new Email)
        ->text("Line one\nLine two");

    $html = EmailBodyExtractor::fromMessage($message);

    expect($html)
        ->toContain('<pre')
        ->toContain('Line one')
        ->toContain('Line two');
});

it('prefers html over plain text when both are present', function () {
    $message = (new Email)
        ->html('<p>HTML version</p>')
        ->text('Plain version');

    expect(EmailBodyExtractor::fromMessage($message))
        ->toBe('<p>HTML version</p>');
});
