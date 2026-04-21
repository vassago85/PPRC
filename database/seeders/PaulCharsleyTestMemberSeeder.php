<?php

namespace Database\Seeders;

use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Local/staging test member: normal "member" role only (no committee).
 *
 * Membership number 990001 sits in a high numeric range so the club’s real
 * sequential allocator (max+1 from production imports) will not collide until
 * the club has ~990k numeric members — effectively reserved for dev/test.
 */
class PaulCharsleyTestMemberSeeder extends Seeder
{
    public const TEST_MEMBER_NUMBER = '990001';

    public function run(): void
    {
        Role::findOrCreate('member', 'web');

        if (! MembershipType::query()->where('slug', 'full-member')->exists()) {
            $this->call(MembershipTypesSeeder::class);
        }

        $user = User::updateOrCreate(
            ['email' => 'p.charsley@gmail.com'],
            [
                'name' => 'Paul Charsley',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'created_via_import' => false,
            ],
        );

        $user->syncRoles(['member']);

        $member = Member::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'Paul',
                'last_name' => 'Charsley',
                'known_as' => null,
                'membership_number' => self::TEST_MEMBER_NUMBER,
                'phone_country_code' => '+27',
                'phone_number' => '821234567',
                'address_line1' => 'Test address (seed)',
                'city' => 'Pretoria',
                'province' => 'Gauteng',
                'postal_code' => '0001',
                'country' => 'South Africa',
                'date_of_birth' => '1980-01-15',
                'shooting_disciplines' => ['PRS'],
                'status' => MemberStatus::Active,
                'join_date' => now()->toDateString(),
            ],
        );

        $type = MembershipType::query()->where('slug', 'full-member')->firstOrFail();

        $periodStart = now()->startOfYear()->toDateString();
        $periodEnd = now()->startOfYear()->addYear()->subDay()->toDateString();

        $membership = Membership::query()
            ->where('member_id', $member->id)
            ->where('membership_type_id', $type->id)
            ->where('status', MembershipStatus::Active)
            ->first();

        if ($membership) {
            $membership->update([
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'price_cents_snapshot' => $type->price_cents,
                'membership_type_slug_snapshot' => $type->slug,
                'membership_type_name_snapshot' => $type->name,
                'approved_at' => now(),
            ]);
        } else {
            Membership::create([
                'member_id' => $member->id,
                'membership_type_id' => $type->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => MembershipStatus::Active,
                'price_cents_snapshot' => $type->price_cents,
                'membership_type_slug_snapshot' => $type->slug,
                'membership_type_name_snapshot' => $type->name,
                'approved_at' => now(),
            ]);
        }
    }
}
