<?php

return [
    /*
    | When true, the portal shop checkout may offer Paystack card payment.
    | Requires a completed Paystack initialize + webhook flow for shop orders.
    | Until then, keep false and use manual EFT + proof upload only.
    */
    'paystack_enabled' => (bool) env('SHOP_PAYSTACK_ENABLED', false),
];
