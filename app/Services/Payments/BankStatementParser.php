<?php

namespace App\Services\Payments;

use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Reads a bank statement CSV export and hands back the money that came in.
 *
 * Bank exports are not a format so much as a habit. FNB's puts six lines of
 * account preamble above the header row; others quote every field, use
 * semicolons, split debit and credit into separate columns, or write amounts
 * European-style. So rather than assume a layout, this finds the header row by
 * looking for recognisable column names and maps from there.
 *
 * Only credits are returned. Fees, transfers out and interest lines are money
 * the club spent or earned, not a member settling up.
 */
class BankStatementParser
{
    protected const DATE_HEADERS = [
        'date', 'transaction date', 'txn date', 'posting date', 'value date',
        'effective date', 'trans date',
    ];

    protected const AMOUNT_HEADERS = ['amount', 'transaction amount', 'value', 'trans amount'];

    protected const CREDIT_HEADERS = ['credit', 'credit amount', 'money in', 'deposit', 'deposits', 'in'];

    protected const DEBIT_HEADERS = ['debit', 'debit amount', 'money out', 'withdrawal', 'out'];

    protected const DESCRIPTION_HEADERS = [
        'description', 'narrative', 'details', 'detail', 'reference', 'memo',
        'transaction description', 'payment reference', 'narration', 'particulars',
    ];

    /** Guards against a header-like row appearing far into a huge file. */
    protected const MAX_PREAMBLE_ROWS = 30;

    public function parse(string $contents): StatementParseResult
    {
        $rows = $this->rows($contents);

        if ($rows === []) {
            throw new RuntimeException('That file is empty.');
        }

        [$headerAt, $columns] = $this->locateHeader($rows);

        $credits = [];
        $debits = 0;
        $ignored = 0;

        foreach ($rows as $lineNumber => $row) {
            if ($lineNumber <= $headerAt) {
                continue;
            }

            $description = trim((string) ($row[$columns['description']] ?? ''));
            $cents = $this->amountFor($row, $columns);

            if ($description === '' && $cents === null) {
                continue;
            }

            if ($cents === null || $cents === 0) {
                $ignored++;

                continue;
            }

            if ($cents < 0) {
                $debits++;

                continue;
            }

            $credits[] = new StatementLine(
                row: $lineNumber,
                date: $columns['date'] !== null
                    ? $this->dateFor((string) ($row[$columns['date']] ?? ''))
                    : null,
                amountCents: $cents,
                description: $description,
            );
        }

        if ($credits === []) {
            throw new RuntimeException('No money-in lines were found in that file.');
        }

        return new StatementParseResult($credits, $debits, $ignored);
    }

    /**
     * Non-blank rows, keyed by their 1-based line number in the uploaded file so
     * the admin can find any line we report on again.
     *
     * @return array<int, array<int, string>>
     */
    protected function rows(string $contents): array
    {
        // Strip a UTF-8 BOM, which Excel loves to add and which would otherwise
        // hide the first header cell behind invisible bytes.
        $contents = preg_replace('/^\x{FEFF}/u', '', $contents) ?? $contents;

        $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];
        $delimiter = $this->delimiter($lines);

        $rows = [];
        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            $rows[$index + 1] = array_map(
                fn ($cell) => trim((string) $cell),
                str_getcsv($line, $delimiter, '"', '\\'),
            );
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $lines
     */
    protected function delimiter(array $lines): string
    {
        $sample = implode("\n", array_slice($lines, 0, 20));

        $counts = [
            ',' => substr_count($sample, ','),
            ';' => substr_count($sample, ';'),
            "\t" => substr_count($sample, "\t"),
            '|' => substr_count($sample, '|'),
        ];

        arsort($counts);

        return (string) array_key_first($counts);
    }

    /**
     * Find the header row and which column each thing we need lives in.
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array{0: int, 1: array{date: int|null, description: int, amount: int|null, credit: int|null, debit: int|null}}
     */
    protected function locateHeader(array $rows): array
    {
        foreach (array_slice($rows, 0, self::MAX_PREAMBLE_ROWS, true) as $index => $row) {
            $description = $this->columnFor($row, self::DESCRIPTION_HEADERS);
            $amount = $this->columnFor($row, self::AMOUNT_HEADERS);
            $credit = $this->columnFor($row, self::CREDIT_HEADERS);

            // A statement needs somewhere to say what the payment was and how
            // much it was for. Everything else is optional.
            if ($description === null || ($amount === null && $credit === null)) {
                continue;
            }

            return [$index, [
                'date' => $this->columnFor($row, self::DATE_HEADERS),
                'description' => $description,
                'amount' => $amount,
                'credit' => $credit,
                'debit' => $this->columnFor($row, self::DEBIT_HEADERS),
            ]];
        }

        throw new RuntimeException(
            'Could not find the column headings in that file. It needs a header row with a '
            .'description column (Description, Narrative, Details or Reference) and an amount '
            .'column (Amount, or Credit and Debit).',
        );
    }

    /**
     * @param  array<int, string>  $row
     * @param  array<int, string>  $names
     */
    protected function columnFor(array $row, array $names): ?int
    {
        foreach ($row as $index => $cell) {
            if (in_array(strtolower(trim($cell)), $names, true)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $row
     * @param  array{date: int|null, description: int, amount: int|null, credit: int|null, debit: int|null}  $columns
     */
    protected function amountFor(array $row, array $columns): ?int
    {
        if ($columns['amount'] !== null) {
            return $this->cents((string) ($row[$columns['amount']] ?? ''));
        }

        $credit = $columns['credit'] !== null
            ? $this->cents((string) ($row[$columns['credit']] ?? ''))
            : null;

        if ($credit !== null && $credit !== 0) {
            return abs($credit);
        }

        $debit = $columns['debit'] !== null
            ? $this->cents((string) ($row[$columns['debit']] ?? ''))
            : null;

        // A value in the debit column is money out however it was signed.
        return $debit !== null && $debit !== 0 ? -abs($debit) : null;
    }

    /**
     * Parse an amount into cents, coping with currency symbols, thousands
     * separators either way round, and the three ways a statement can write a
     * negative: "-17.00", "17.00-" and "(17.00)".
     */
    protected function cents(string $raw): ?int
    {
        $clean = trim($raw);

        if ($clean === '') {
            return null;
        }

        $negative = str_starts_with($clean, '-')
            || str_ends_with($clean, '-')
            || (str_starts_with($clean, '(') && str_ends_with($clean, ')'));

        $number = preg_replace('/[^0-9.,]/', '', $clean);

        if ($number === null || $number === '' || ! preg_match('/\d/', $number)) {
            return null;
        }

        $lastDot = strrpos($number, '.');
        $lastComma = strrpos($number, ',');

        if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
            // "1.234,56" — comma is the decimal point.
            $number = str_replace(',', '.', str_replace('.', '', $number));
        } else {
            $number = str_replace(',', '', $number);
        }

        if (! is_numeric($number)) {
            return null;
        }

        $cents = (int) round(((float) $number) * 100);

        return $negative ? -$cents : $cents;
    }

    protected function dateFor(string $raw): ?Carbon
    {
        $clean = trim($raw);

        if ($clean === '') {
            return null;
        }

        foreach (['Y/m/d', 'Y-m-d', 'd/m/Y', 'd-m-Y', 'd/m/y', 'd M Y', 'd F Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $clean);
            } catch (\Throwable) {
                continue;
            }

            // createFromFormat is happy to read "28/07/2026" as year 28, so
            // sanity-check the result before trusting it.
            if ($parsed !== false && $parsed->year >= 2000 && $parsed->year <= 2100) {
                return $parsed->startOfDay();
            }
        }

        try {
            return Carbon::parse($clean)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
