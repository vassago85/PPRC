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

    /*
    |--------------------------------------------------------------------------
    | Stale signup cleanup
    |--------------------------------------------------------------------------
    |
    | People who register but never finish (never verify their email, or verify
    | then never pick a membership) otherwise sit in "Pending" forever and clog
    | the "Members to onboard" queue. The cleanup command nudges them once to
    | finish, then — if they still haven't after the grace window — moves them
    | to the "Abandoned signup" status. It is fully reversible: if they come
    | back and verify / apply, they return to Pending automatically.
    |
    */

    'stale_signup_months' => (int) env('MEMBERSHIP_STALE_SIGNUP_MONTHS', 6),

    'stale_signup_grace_days' => (int) env('MEMBERSHIP_STALE_SIGNUP_GRACE_DAYS', 14),

    // People who chose a membership but never paid are chased on a shorter,
    // gentler clock than the never-verified / never-chose cohorts: they did
    // engage — the club just never saw the money — so 30 days unpaid triggers
    // the nudge, then the same grace window above before archiving. The clock
    // runs from when the payment was requested, not when they first registered.
    'stale_unpaid_signup_days' => (int) env('MEMBERSHIP_STALE_UNPAID_SIGNUP_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Lifecycle bucket windows
    |--------------------------------------------------------------------------
    |
    | These define the lifecycle buckets on the Member model, and therefore
    | every list, tab badge, dashboard card and scheduled reminder that counts
    | members. They used to be hardcoded separately in each place, which is why
    | "lapsed" meant 60 days on the members list, 180 days to the renewal
    | reminder command, and no window at all on the membership overview.
    |
    */

    // Active members inside this many days of expiry are "renewal due".
    'renewal_due_days' => (int) env('MEMBERSHIP_RENEWAL_DUE_DAYS', 30),

    // Expired this recently and still worth chasing back.
    'recently_lapsed_days' => (int) env('MEMBERSHIP_RECENTLY_LAPSED_DAYS', 60),

    // Expired longer ago than this and no longer part of the renewal effort.
    // Replaces the old stored "inactive" status.
    'long_lapsed_months' => (int) env('MEMBERSHIP_LONG_LAPSED_MONTHS', 6),

];
