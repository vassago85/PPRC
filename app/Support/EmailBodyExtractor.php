<?php

namespace App\Support;

use Symfony\Component\Mime\Email;

class EmailBodyExtractor
{
    public static function fromMessage(Email $message): ?string
    {
        $html = self::bodyToString($message->getHtmlBody());
        if (filled($html)) {
            return $html;
        }

        $text = self::bodyToString($message->getTextBody());
        if (! filled($text)) {
            return null;
        }

        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><pre style="font-family:ui-sans-serif,system-ui,sans-serif;white-space:pre-wrap;margin:1rem;">'
            .htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            .'</pre></body></html>';
    }

    private static function bodyToString(mixed $body): ?string
    {
        if ($body === null) {
            return null;
        }

        if (is_resource($body)) {
            $contents = stream_get_contents($body);

            return $contents === false ? null : $contents;
        }

        return is_string($body) ? $body : null;
    }
}
