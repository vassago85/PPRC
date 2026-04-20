<?php

namespace App\Console\Commands;

use App\Enums\MembershipStatus;
use App\Models\Member;
use App\Models\Membership;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AgeSubMembers extends Command
{
    protected $signature = 'memberships:age-sub-members
                            {--dry-run : List affected memberships without updating}';

    protected $description = 'Expire sub-member memberships (e.g. juniors) whose age has now exceeded the type age rule.';

    public function handle(): int
    {
        $today = Carbon::now()->startOfDay();
        $affected = 0;

        $query = Membership::query()
            ->whereIn('status', [
                MembershipStatus::Active->value,
                MembershipStatus::PendingApproval->value,
                MembershipStatus::PendingPayment->value,
            ])
            ->whereHas('type', fn ($q) => $q->where('is_sub_membership', true)->where('has_age_requirement', true))
            ->with(['member', 'type']);

        foreach ($query->cursor() as $membership) {
            /** @var Member $member */
            $member = $membership->member;
            if (! $member || ! $membership->type) {
                continue;
            }

            if ($membership->type->satisfiesAge($member->date_of_birth, $today)) {
                continue;
            }

            $affected++;
            $this->line(sprintf(
                '%s %s (#%d) no longer meets age rule for %s',
                $membership->type->name,
                $member->fullName(),
                $membership->id,
                $membership->membership_type_slug_snapshot ?? '—',
            ));

            if (! $this->option('dry-run')) {
                $membership->update([
                    'status' => MembershipStatus::Expired,
                    'admin_notes' => trim(($membership->admin_notes ?? '')
                        ."\nAuto-expired by memberships:age-sub-members on ".$today->toDateString()),
                ]);
            }
        }

        $this->info(($this->option('dry-run') ? '[dry-run] ' : '')."Processed {$affected} sub-membership(s).");

        return self::SUCCESS;
    }
}
