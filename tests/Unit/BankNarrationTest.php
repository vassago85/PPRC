<?php

use App\Services\Payments\BankNarration;

/*
|--------------------------------------------------------------------------
| Reference extraction
|--------------------------------------------------------------------------
|
| Every line below is a real narration off the club's bank statement. The
| parser never strips a prefix off the front — it hunts for the club reference
| anywhere in the line — which is what lets it cope with the bank's name being
| bolted on, glued on, or wrapped around the reference entirely.
|
*/

it('finds the club reference however the bank mangled the line', function (string $line, string $expected) {
    expect(BankNarration::make($line)->referenceTokens('PPRC'))->toContain($expected);
})->with([
    'bank name in front' => ['ABSA BANK PPRC-M14-103', 'M14-103'],
    'another bank in front' => ['CAPITEC PPRC-M13-104', 'M13-104'],
    'bank name glued on' => ['INVESTECPBPPRC-M14-85', 'M14-85'],
    'wrapped in app narration' => ['FNB APP PAYMENT FROM PPRC-M13-101', 'M13-101'],
    'wrapped in internet banking narration' => ['INT-BANKING PMT FRM PPRC-M14-100', 'M14-100'],
    'separators stripped' => ['PPRCM1489', 'M1489'],
    'membership reference flattened' => ['AF BANK PPRC202607250001', '202607250001'],
    'membership reference intact' => ['PPRC-20260105-0006', '20260105-0006'],
    'current short reference' => ['PPRC-M103', 'M103'],
    'lower case' => ['pprc-m103', 'M103'],
    'spaces instead of dashes' => ['PPRC 20260725 0001', '20260725-0001'],

    // Everything below is off a real FNB statement export.
    'spaces for dashes' => ['FNB APP PAYMENT FROM  PPRC M12 63', 'M12-63'],
    'dot inside the reference' => ['PPRC M12. 60', 'M12-60'],
    'dot straight after the prefix' => ['PPRC.  M12  59', 'M12-59'],
    'stray dash as its own word' => ['CAPITEC   PPRC-M12 - 14', 'M12-14'],
    'member typed three Ps' => ['FNB APP PAYMENT FROM  PPPRC-M12-61', 'M12-61'],
    'legacy MEM marker' => ['FNB APP PAYMENT FROM  PPRCMEM20260502-0002', 'MEM20260502-0002'],
    'surname after the reference' => ['INT-BANKING PMT FRM   PPRC-M12-55 RUSSMANN', 'M12-55'],
    'online banking narration' => ['FNB OB PMT            PPRC-M12-53', 'M12-53'],
    'name before the reference' => ['FNB OB PMT            PL FOURIE - PPRC-M12', 'M12'],
]);

it('does not swallow a surname the bank ran into the reference with a dot', function () {
    expect(BankNarration::make('PPRC-M13-95 J.NEL')->referenceTokens('PPRC'))->toBe(['M13-95']);
});

it('reads a surname the bank ran into an initial', function () {
    expect(BankNarration::make('PPRC-M13-95 J.NEL')->nameWords())->toBe(['NEL']);
});

it('splits a hyphenated surname glued to the bank name', function () {
    expect(BankNarration::make('INVESTECPBOKennedy Smit-Kenny')->nameWords())
        ->toContain('SMIT')
        ->toContain('KENNY');
});

it('reads a name off a line that leads with a date', function () {
    expect(BankNarration::make('04/07/26 KURT PENEDER')->nameWords())->toBe(['KURT', 'PENEDER']);
});

it('stops reading the reference at the payer name that follows it', function () {
    expect(BankNarration::make('PPRC-M13-95 J NEL')->referenceTokens('PPRC'))->toBe(['M13-95']);
});

it('finds no reference when the bank left only a name', function () {
    expect(BankNarration::make('M BRUMMER')->referenceTokens('PPRC'))->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Name extraction
|--------------------------------------------------------------------------
*/

it('keeps the payer name and drops the bank boilerplate', function () {
    expect(BankNarration::make('FNB APP PAYMENT FROM RUAN DU PLESSIS')->nameWords())
        ->toBe(['RUAN', 'PLESSIS']);
});

it('reads a surname off a line that is nothing but an initial and a name', function () {
    expect(BankNarration::make('M BRUMMER')->nameWords())->toBe(['BRUMMER']);
});

it('does not mistake a reference for a name', function () {
    expect(BankNarration::make('PPRC-M13-95 J NEL')->nameWords())->toBe(['NEL']);
});

it('has no name to offer when the line is only a reference', function () {
    expect(BankNarration::make('ABSA BANK PPRC-M14-103')->nameWords())->toBe([]);
});
