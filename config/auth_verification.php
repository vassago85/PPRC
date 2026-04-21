<?php

return [

    'pin_length' => 6,

    /** Minutes the emailed PIN remains valid. */
    'pin_expires_minutes' => (int) env('EMAIL_VERIFICATION_PIN_EXPIRES', 60),

    /** Max wrong PIN attempts before lockout (per user). */
    'pin_max_attempts' => 8,

    /** Lockout seconds after too many wrong PINs. */
    'pin_lockout_seconds' => 900,

];
