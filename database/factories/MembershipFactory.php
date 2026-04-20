<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    public function definition(): array
    {
        $start = Carbon::now()->startOfYear();
        $type = MembershipType::factory()->create();

        return [
            'member_id' => Member::factory(),
            'membership_type_id' => $type->id,
            'period_start' => $start,
            'period_end' => $start->copy()->addMonths($type->duration_months)->subDay(),
            'status' => 'active',
            'price_cents_snapshot' => $type->price_cents,
            'membership_type_slug_snapshot' => $type->slug,
            'membership_type_name_snapshot' => $type->name,
        ];
    }
}
