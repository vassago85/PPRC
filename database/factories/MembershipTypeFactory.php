<?php

namespace Database\Factories;

use App\Models\MembershipType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MembershipType>
 */
class MembershipTypeFactory extends Factory
{
    protected $model = MembershipType::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'slug' => Str::slug($name).'-'.Str::random(4),
            'name' => ucwords($name),
            'description' => $this->faker->sentence(),
            'price_cents' => $this->faker->numberBetween(50000, 250000),
            'duration_months' => 12,
            'is_active' => true,
            'show_on_registration' => true,
            'requires_manual_approval' => true,
            'assign_membership_number_on_approval' => true,
            'counts_as_member' => true,
            'allows_sub_members' => false,
            'allowed_sub_member_type_slugs' => null,
            'has_age_requirement' => false,
            'age_requirement_type' => null,
            'age_min' => null,
            'age_max' => null,
            'sort_order' => 0,
        ];
    }
}
