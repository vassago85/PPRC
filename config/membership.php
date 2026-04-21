<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sequential membership numbers
    |--------------------------------------------------------------------------
    |
    | New numbers are max(existing numeric numbers, including soft-deleted
    | members) + 1, never filling "gaps" from resigned or deleted members.
    | That keeps historical imports (CSV results, certificates) unambiguous.
    |
    */

    'number_start' => (int) env('MEMBERSHIP_NUMBER_START', 1),

    /** If set (e.g. 5), numbers are left-padded with zeros to this width. */
    'number_pad_length' => env('MEMBERSHIP_NUMBER_PAD_LENGTH') !== null && env('MEMBERSHIP_NUMBER_PAD_LENGTH') !== ''
        ? (int) env('MEMBERSHIP_NUMBER_PAD_LENGTH')
        : null,

];
