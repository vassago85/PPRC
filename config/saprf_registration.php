<?php

/**
 * Default division / category lists for match registration.
 *
 * Equipment divisions follow SAPRF / Precision Rifle Series – South Africa
 * naming (Classic hunting-style, Factory, Limited .308, Open). Open
 * competitors may additionally declare Ladies / Junior / Senior / Mil–LEO
 * tracks where applicable — aligned with IPRF-style Open sub-eligibility.
 *
 * Per-event overrides live on `events.registration_*_options` (JSON arrays).
 * When an override array is non-empty, only those labels are offered.
 */
return [

    'equipment_divisions' => [
        'Classic',
        'Factory',
        'Limited',
        'Open',
    ],

    /**
     * Second field at registration: Open sub-track or “not applicable”.
     */
    'registration_categories' => [
        'General',
        'Ladies',
        'Junior',
        'Senior',
        'Mil/LEO',
        'Not applicable',
    ],
];
