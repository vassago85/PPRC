<?php

namespace App\Services\Membership;

use App\Models\MembershipPayment;
use App\Models\SiteSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generates EFT payment references in the format PREFIX-YYYYMMDD-####
 * where #### is a daily sequence counter. Uniqueness is enforced across
 * both membership_payments.reference and members.membership_number so
 * bank reconciliation never collides.
 */
class PaymentReferenceGenerator
{
    public function generate(?Carbon $date = null): string
    {
        $date ??= Carbon::today();
        $prefix = $this->resolvePrefix();
        $dateStr = $date->format('Ymd');
        $base = "{$prefix}-{$dateStr}";

        return DB::transaction(function () use ($base) {
            $refs = MembershipPayment::query()
                ->where('reference', 'like', "{$base}-%")
                ->lockForUpdate()
                ->pluck('reference');

            $lastSeq = 0;
            foreach ($refs as $ref) {
                $parts = explode('-', $ref);
                $seq = (int) end($parts);
                $lastSeq = max($lastSeq, $seq);
            }

            $next = $lastSeq + 1;
            $ref = sprintf('%s-%04d', $base, $next);

            while ($this->existsAnywhere($ref)) {
                $next++;
                $ref = sprintf('%s-%04d', $base, $next);
            }

            return $ref;
        });
    }

    protected function existsAnywhere(string $ref): bool
    {
        return MembershipPayment::withTrashed()->where('reference', $ref)->exists()
            || DB::table('members')->where('membership_number', $ref)->exists();
    }

    /**
     * Resolve the prefix in this priority order:
     *
     *   1. SiteSetting `payments.bank.reference_prefix` (if non-empty)
     *      — the new clean field admins can set in the UI.
     *   2. SiteSetting `payments.bank.reference_format` (legacy field
     *      with values like "PPRC-MEM-{id}") — we strip the templating
     *      and use everything before the first `{` (or before `-{`).
     *   3. config('membership.payment_ref_prefix') from .env.
     *   4. Hard default 'PPRC'.
     */
    protected function resolvePrefix(): string
    {
        try {
            $direct = trim((string) SiteSetting::get('payments.bank.reference_prefix', ''));
            if ($direct !== '') {
                return $this->sanitizePrefix($direct);
            }

            $legacy = trim((string) SiteSetting::get('payments.bank.reference_format', ''));
            if ($legacy !== '') {
                $bracePos = strpos($legacy, '{');
                if ($bracePos !== false) {
                    $legacy = rtrim(substr($legacy, 0, $bracePos), '-');
                }
                if ($legacy !== '') {
                    return $this->sanitizePrefix($legacy);
                }
            }
        } catch (\Throwable) {
            // Setting tables not migrated yet — fall through to config.
        }

        return $this->sanitizePrefix(
            (string) config('membership.payment_ref_prefix', 'PPRC') ?: 'PPRC'
        );
    }

    private function sanitizePrefix(string $value): string
    {
        // Banks tend to choke on anything other than alphanumerics and
        // dashes in references; clamp the prefix to that subset.
        $clean = preg_replace('/[^A-Za-z0-9-]+/', '', $value) ?: 'PPRC';

        return strtoupper(trim($clean, '-'));
    }
}
