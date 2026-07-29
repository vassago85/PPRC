<?php

use App\Services\Payments\BankStatementParser;

function parser(): BankStatementParser
{
    return new BankStatementParser;
}

/**
 * Mirrors a real FNB "account transaction history" export: six lines of account
 * preamble above the header, a space after every comma, and money-out lines
 * mixed in with the deposits.
 */
function fnbExport(): string
{
    return <<<'CSV'
    ACCOUNT TRANSACTION HISTORY

    Name:, Test, Club
    Account:, 00000000000, [Gold Business Account]
    Balance:, 1350.00, 1350.00

    Date, Amount, Balance, Description
    2026/07/28, 450.00, 1350.00, ABSA BANK PPRC-M14-103
    2026/07/28, 500.00, 900.00, FNB APP PAYMENT FROM  PPRC-M13-101
    2026/07/27, 400.00, 400.00, M BRUMMER
    2026/07/11, -96.00, 0.00, #MONTHLY ACCOUNT FEE
    2026/07/11, 0.00, 96.00, BIS/INT 1 ON TRUE TIERING = 5.00
    2026/07/07, -15000.00, 96.00, INTERNAL TRF
    CSV;
}

it('finds the header row underneath the account preamble', function () {
    $result = parser()->parse(fnbExport());

    expect($result->credits)->toHaveCount(3)
        ->and($result->credits[0]->description)->toBe('ABSA BANK PPRC-M14-103')
        ->and($result->credits[0]->amountCents)->toBe(45000)
        ->and($result->credits[0]->date->toDateString())->toBe('2026-07-28');
});

it('keeps the money in and leaves the money out alone', function () {
    $result = parser()->parse(fnbExport());

    expect($result->totalCents())->toBe(135000)
        ->and($result->debits)->toBe(2)
        ->and($result->ignored)->toBe(1);
});

it('reports the row number so a line can be found in the file again', function () {
    $result = parser()->parse(fnbExport());

    // Header is row 7 in the original export, so the first deposit is row 8.
    expect($result->credits[0]->row)->toBe(8);
});

it('reads a statement that splits debit and credit into separate columns', function () {
    $csv = <<<'CSV'
    Transaction Date,Narrative,Debit,Credit,Balance
    2026/07/28,ABSA BANK PPRC-M14-103,,450.00,1350.00
    2026/07/27,MONTHLY FEE,96.00,,1254.00
    CSV;

    $result = parser()->parse($csv);

    expect($result->credits)->toHaveCount(1)
        ->and($result->credits[0]->amountCents)->toBe(45000)
        ->and($result->debits)->toBe(1);
});

it('reads semicolon-separated exports with european amounts', function () {
    $csv = <<<'CSV'
    Date;Details;Amount
    28-07-2026;ABSA BANK PPRC-M14-103;1.234,56
    CSV;

    $result = parser()->parse($csv);

    expect($result->credits[0]->amountCents)->toBe(123456)
        ->and($result->credits[0]->date->toDateString())->toBe('2026-07-28');
});

it('understands the three ways a statement writes a negative', function (string $amount) {
    $csv = "Date,Description,Amount\n"
        ."2026/07/28,A REAL PAYMENT,100.00\n"
        ."2026/07/28,SOME FEE,{$amount}";

    $result = parser()->parse($csv);

    expect($result->credits)->toHaveCount(1)
        ->and($result->debits)->toBe(1);
})->with(['-17.00', '17.00-', '(17.00)']);

it('strips a currency symbol and thousands separator', function () {
    $csv = <<<'CSV'
    Date,Description,Amount
    2026/07/28,ABSA BANK PPRC-M14-103,"R 1,450.00"
    CSV;

    expect(parser()->parse($csv)->credits[0]->amountCents)->toBe(145000);
});

it('copes with a byte order mark on the header', function () {
    $csv = "\u{FEFF}Date,Description,Amount\n2026/07/28,ABSA BANK PPRC-M14-103,450.00";

    expect(parser()->parse($csv)->credits)->toHaveCount(1);
});

it('refuses a file with no recognisable column headings', function () {
    parser()->parse("just,some,numbers\n1,2,3");
})->throws(RuntimeException::class, 'Could not find the column headings');

it('refuses an empty file', function () {
    parser()->parse('   ');
})->throws(RuntimeException::class, 'empty');

it('says so when a statement has no deposits at all', function () {
    $csv = <<<'CSV'
    Date, Amount, Balance, Description
    2026/07/11, -96.00, 0.00, #MONTHLY ACCOUNT FEE
    CSV;

    parser()->parse($csv);
})->throws(RuntimeException::class, 'No money-in lines');
