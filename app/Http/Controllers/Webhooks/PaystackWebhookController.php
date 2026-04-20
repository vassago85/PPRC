<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Paystack webhook receiver.
 *
 * Production responsibilities (wired in Phase 3):
 *  - Verify x-paystack-signature HMAC-SHA512 over raw body with secret.
 *  - Idempotent processing keyed on event id + reference.
 *  - Dispatch event-specific handlers (charge.success, transfer.*).
 *
 * Phase 0 ships the route + signature check scaffolding so deploys never
 * accidentally expose an unverified endpoint.
 */
class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $secret = SiteSetting::get('payments.paystack.webhook_secret')
            ?? config('services.paystack.webhook_secret')
            ?? env('PAYSTACK_WEBHOOK_SECRET');

        if (! $secret) {
            Log::warning('Paystack webhook received but no secret configured; rejecting.');

            return response('', 503);
        }

        $signature = $request->header('x-paystack-signature');
        $body = $request->getContent();
        $expected = hash_hmac('sha512', $body, $secret);

        if (! $signature || ! hash_equals($expected, $signature)) {
            Log::warning('Paystack webhook signature mismatch.', [
                'has_signature' => (bool) $signature,
                'ip' => $request->ip(),
            ]);

            return response('', 401);
        }

        Log::info('Paystack webhook accepted (Phase 0 stub).', [
            'event' => $request->input('event'),
            'reference' => $request->input('data.reference'),
        ]);

        return response('', 200);
    }
}
