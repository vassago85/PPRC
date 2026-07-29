<?php

namespace App\Services\Payments;

/**
 * One line off the bank statement, pulled apart into the pieces we can
 * reconcile against.
 *
 * Real statement lines arrive mangled in a handful of predictable ways, and
 * every one of these came off the club's own account:
 *
 *   "ABSA BANK PPRC-M14-103"            bank name bolted on the front
 *   "INVESTECPBPPRC-M14-85"             bank name glued on with no space
 *   "FNB APP PAYMENT FROM PPRC-M13-101" narration wrapped around it
 *   "PPRCM1489"                         separators stripped entirely
 *   "PPRC-M13-95 J NEL"                 reference plus a name
 *   "M BRUMMER"                         no reference at all, just a name
 *
 * So we never try to strip a prefix off the front. We look for the club
 * reference *anywhere* in the line, and treat whatever words are left over as
 * a possible human name.
 */
class BankNarration
{
    /**
     * Words banks staple onto a payment narration. Stripped before we treat
     * what remains as somebody's name, otherwise "FNB APP PAYMENT FROM RUAN DU
     * PLESSIS" would have us hunting for a member called Payment.
     */
    public const BANK_NOISE = [
        'ABSA', 'BANK', 'BANKING', 'CAPITEC', 'FNB', 'NEDBANK', 'STANDARD', 'STD',
        'INVESTEC', 'INVESTECPB', 'TYME', 'DISCOVERY', 'AFRICAN', 'BIDVEST',
        'SASFIN', 'MERCANTILE', 'GRINDROD', 'OLYMPUS', 'ITHALA',
        'APP', 'PAYMENT', 'PAYMENTS', 'PMT', 'PMNT', 'PAYM', 'FROM', 'FRM',
        'INT', 'IB', 'INTERNET', 'ONLINE', 'MOBILE', 'CELL', 'DIGITAL', 'WEB',
        'EFT', 'ACB', 'CREDIT', 'TRANSFER', 'TRF', 'TFR', 'IMMEDIATE', 'INSTANT',
        'DEPOSIT', 'DEP', 'CASH', 'PAYSHAP', 'RTC', 'REF', 'REFERENCE', 'PAID',
        'OB', 'AF', 'ABS', 'NED', 'CAP',
    ];

    /**
     * Surname particles that match far too many people to search on their own.
     */
    public const NAME_PARTICLES = [
        'VAN', 'DER', 'DEN', 'DIE', 'DU', 'DE', 'LA', 'LE', 'DOS', 'DAS', 'JR', 'SNR',
    ];

    /** How many whitespace-separated words after the prefix we'll glue together. */
    protected const MAX_REFERENCE_WORDS = 3;

    /** Words we'll look at while gluing, allowing for the bank's stray punctuation. */
    protected const MAX_SCANNED_WORDS = 6;

    public function __construct(public readonly string $raw) {}

    public static function make(string $raw): self
    {
        return new self($raw);
    }

    /**
     * Upper-cased, single-spaced. Bank exports are inconsistent about case and
     * padding, and every comparison we make downstream is case-insensitive.
     */
    public function normalised(): string
    {
        return trim((string) preg_replace('/\s+/', ' ', strtoupper($this->raw)));
    }

    /**
     * Every plausible club reference in the line, canonicalised to
     * dash-separated segments (e.g. "M14-103", "20260105-0006", "M1489").
     *
     * Because "PPRC 20260725 0001" and "PPRC-M13-95 450 THANKS" both put
     * reference-ish words after the prefix, we emit the first word on its own
     * *and* the glued runs, then let the database throw out the nonsense.
     *
     * @return array<int, string>
     */
    public function referenceTokens(string $prefix): array
    {
        $text = $this->normalised();
        $tokens = [];

        $offset = 0;
        while ($prefix !== '' && ($at = strpos($text, $prefix, $offset)) !== false) {
            $offset = $at + strlen($prefix);

            foreach ($this->candidatesAfter(substr($text, $offset)) as $token) {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique(array_filter($tokens)));
    }

    /**
     * Words that could be part of a person's name, once the bank's boilerplate
     * and anything reference-shaped is out of the way.
     *
     * @return array<int, string>
     */
    public function nameWords(string $prefix = ''): array
    {
        $words = [];

        foreach (explode(' ', $this->normalised()) as $chunk) {
            // The club's own prefix, left stranded when the bank spaced the
            // reference out as "PPRC M12 60", is not somebody's surname.
            if ($prefix !== '' && trim($chunk, '.-/,:;#*') === $prefix) {
                continue;
            }

            // Anything carrying a digit is reference material, never a name.
            if ($chunk === '' || preg_match('/\d/', $chunk) === 1) {
                continue;
            }

            // Split on punctuation so a surname the bank ran into an initial
            // ("J.NEL") or hyphenated ("SMIT-KENNY") still comes apart.
            foreach (preg_split('/[^A-Z\']+/', $chunk) ?: [] as $word) {
                if ($this->looksLikeName($word)) {
                    $words[] = $word;
                }
            }
        }

        $meaningful = array_values(array_filter(
            $words,
            fn (string $word) => strlen($word) >= 3 && ! in_array($word, self::NAME_PARTICLES, true),
        ));

        // A line that is nothing but particles ("DU TOIT" clipped to "DU") is
        // still better searched on than not searched on at all.
        return $meaningful !== [] ? $meaningful : array_values($words);
    }

    /**
     * Reference candidates from the text immediately following a prefix hit.
     *
     * @return array<int, string>
     */
    protected function candidatesAfter(string $tail): array
    {
        $taken = [];
        $scanned = 0;

        foreach (array_filter(explode(' ', trim($tail))) as $word) {
            if (count($taken) >= self::MAX_REFERENCE_WORDS || $scanned >= self::MAX_SCANNED_WORDS) {
                break;
            }

            $scanned++;
            $fragment = $this->referenceFragment($word);

            if ($fragment === null) {
                break;
            }

            // A word that is nothing but punctuation is the bank's spacing, not
            // the end of the reference: "PPRC.  M12  59", "PPRC-M12 - 14".
            if ($fragment !== '') {
                $taken[] = $fragment;
            }
        }

        $candidates = [];
        for ($length = 1; $length <= count($taken); $length++) {
            $candidates[] = $this->canonicalise(implode('-', array_slice($taken, 0, $length)));
        }

        return array_filter($candidates);
    }

    /**
     * The reference-bearing part of a word.
     *
     * Returns '' when the word is only the bank's punctuation, and null once
     * we've run past the reference into the payer's name — which is what stops
     * "PPRC-M13-95 J.NEL" from being read as one long reference.
     */
    protected function referenceFragment(string $word): ?string
    {
        $stripped = trim($word, '.-/,:;#*');

        if ($stripped === '') {
            return '';
        }

        if (preg_match('#^[A-Z0-9][A-Z0-9./-]*$#', $stripped) !== 1) {
            return null;
        }

        // A digit is what separates "M14-103" from a surname. Bare family
        // markers count too, for banks that space out "PPRC M14 103".
        return preg_match('/\d/', $stripped) === 1
            || in_array($stripped, ['M', 'SHP', 'MEM'], true)
                ? $stripped
                : null;
    }

    protected function looksLikeName(string $word): bool
    {
        return preg_match('/^[A-Z\']{2,}$/', $word) === 1
            && ! in_array($word, self::BANK_NOISE, true);
    }

    /**
     * Collapse anything that isn't alphanumeric into single dashes, so every
     * separator the bank might have used (or dropped) lands in one shape.
     */
    protected function canonicalise(string $token): string
    {
        return trim((string) preg_replace('/[^A-Z0-9]+/', '-', $token), '-');
    }
}
