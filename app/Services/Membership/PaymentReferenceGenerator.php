<?php

namespace App\Services\Membership;

use App\Models\MembershipPayment;
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
        $prefix = (string) config('membership.payment_ref_prefix', 'PPRC');
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
}
