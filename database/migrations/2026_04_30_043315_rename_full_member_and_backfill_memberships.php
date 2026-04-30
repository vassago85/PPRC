<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rename "Full Member" -> "Standard Membership"
        DB::table('membership_types')
            ->where('slug', 'full-member')
            ->update(['name' => 'Standard Membership']);

        // Update any existing membership snapshots
        DB::table('memberships')
            ->where('membership_type_name_snapshot', 'Full Member')
            ->update(['membership_type_name_snapshot' => 'Standard Membership']);

        // 2. Delete junk factory-generated membership types
        DB::table('membership_types')
            ->whereNotIn('slug', [
                'full-member', 'junior', 'spouse', 'life-member', 'pensioner',
            ])
            ->delete();

        // 3. Backfill Membership records for imported members who have none
        $typeMap = DB::table('membership_types')
            ->pluck('id', 'slug')
            ->all();

        $fullMemberId = $typeMap['full-member'] ?? null;
        $juniorId = $typeMap['junior'] ?? null;
        $lifeId = $typeMap['life-member'] ?? null;
        $pensionerId = $typeMap['pensioner'] ?? null;
        $spouseId = $typeMap['spouse'] ?? null;

        if (! $fullMemberId) {
            return;
        }

        // Members without any membership record
        $members = DB::table('members')
            ->leftJoin('memberships', 'members.id', '=', 'memberships.member_id')
            ->whereNull('memberships.id')
            ->whereNull('members.deleted_at')
            ->select('members.*')
            ->get();

        $now = now();

        foreach ($members as $m) {
            // Determine type from the notes field (which has SSMM import info)
            // or from membership_number patterns. Default to Standard Membership.
            $notes = (string) ($m->notes ?? '');
            $typeId = $fullMemberId;
            $typeName = 'Standard Membership';
            $typeSlug = 'full-member';
            $priceCents = 150000;
            $durationMonths = 12;

            // If junior linkage exists, it's a junior
            if ($m->linked_adult_member_id) {
                $typeId = $juniorId ?? $fullMemberId;
                $typeName = $juniorId ? 'Junior' : 'Standard Membership';
                $typeSlug = $juniorId ? 'junior' : 'full-member';
                $priceCents = 0;
            }

            $status = $m->status;
            $membershipStatus = match ($status) {
                'active' => 'active',
                'pending' => 'pending_approval',
                'expired' => 'expired',
                'inactive' => 'cancelled',
                default => 'pending_approval',
            };

            $periodStart = $m->join_date ?? $m->created_at;
            $periodEnd = $m->expiry_date;

            // For active members without expiry, set end of current year
            if (! $periodEnd && $membershipStatus === 'active') {
                $periodEnd = date('Y') . '-12-31';
            }

            DB::table('memberships')->insert([
                'member_id' => $m->id,
                'membership_type_id' => $typeId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => $membershipStatus,
                'price_cents_snapshot' => $priceCents,
                'membership_type_slug_snapshot' => $typeSlug,
                'membership_type_name_snapshot' => $typeName,
                'approved_at' => $membershipStatus === 'active' ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('membership_types')
            ->where('slug', 'full-member')
            ->update(['name' => 'Full Member']);

        DB::table('memberships')
            ->where('membership_type_name_snapshot', 'Standard Membership')
            ->update(['membership_type_name_snapshot' => 'Full Member']);
    }
};
