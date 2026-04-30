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
    | `number_prefix` is prepended verbatim (e.g. "PPRC-"). When empty the
    | number is purely numeric. The allocator also scans legacy patterns like
    | PREFIX-YYYY-#### so imported WP data does not cause collisions.
    |
    */

    'number_start' => (int) env('MEMBERSHIP_NUMBER_START', 1),

    'number_prefix' => env('MEMBERSHIP_NUMBER_PREFIX', 'PPRC-'),

    // SSMM imports are 4-digit zero-padded (PPRC-0150). Auto-allocated numbers
    // for new approved members continue from MAX(existing) + 1 in the same
    // format. DO NOT change this — it rewrites every existing member number.
    'number_pad_length' => env('MEMBERSHIP_NUMBER_PAD_LENGTH') !== null && env('MEMBERSHIP_NUMBER_PAD_LENGTH') !== ''
        ? (int) env('MEMBERSHIP_NUMBER_PAD_LENGTH')
        : 4,

    /*
    |--------------------------------------------------------------------------
    | Payment reference format
    |--------------------------------------------------------------------------
    |
    | EFT payment references: PREFIX-YYYYMMDD-#### with a daily sequence
    | counter ensuring uniqueness across membership_payments + members.
    |
    */

    'payment_ref_prefix' => env('MEMBERSHIP_PAYMENT_REF_PREFIX', 'PPRC'),

    /*
    |--------------------------------------------------------------------------
    | Renewal window
    |--------------------------------------------------------------------------
    |
    | When renewing, if the member's current expiry_date is within this many
    | days of the renewal date, the new period stacks on top of the previous
    | expiry. Otherwise the new period starts from the renewal date.
    |
    */

    'renewal_window_days' => (int) env('MEMBERSHIP_RENEWAL_WINDOW_DAYS', 60),

];
