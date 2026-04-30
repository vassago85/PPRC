<?php

namespace App\Support;

class NameCase
{
    /**
     * Normalize a personal-name string to a sensible Title Case form, but only
     * when the input is *clearly* malformed (entirely lowercase or entirely
     * uppercase). Mixed-case input is preserved verbatim so culturally
     * meaningful variants like "Van der Merwe", "MacDonald", "le Roux" or
     * "O'Brien" are not silently rewritten.
     *
     * Examples:
     *   "alex pienaar"          -> "Alex Pienaar"
     *   "ANDRIES BRUMMER"       -> "Andries Brummer"
     *   "Van der Merwe"         -> "Van der Merwe"   (mixed; left alone)
     *   "Anne-Marie"            -> "Anne-Marie"
     *   "  Jean  Smith "        -> "Jean Smith"
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $clean = preg_replace('/\s+/u', ' ', trim($raw));
        if ($clean === '' || $clean === null) {
            return null;
        }

        if (! self::shouldNormalize($clean)) {
            return $clean;
        }

        // mb_convert_case with MB_CASE_TITLE handles word boundaries including
        // hyphens and apostrophes (Anne-Marie, O'Brien) correctly.
        return mb_convert_case(mb_strtolower($clean, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Only rewrite clearly malformed names — input that has any mixed case is
     * assumed to be intentional (van der Merwe, MacDonald, etc.).
     */
    protected static function shouldNormalize(string $clean): bool
    {
        $letters = preg_replace('/[^\p{L}]/u', '', $clean) ?? '';

        if ($letters === '') {
            return false;
        }

        $lower = mb_strtolower($letters, 'UTF-8');
        $upper = mb_strtoupper($letters, 'UTF-8');

        return $letters === $lower || $letters === $upper;
    }
}
