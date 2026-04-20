<?php

namespace Database\Seeders;

use App\Enums\AgeRequirementType;
use App\Models\MembershipType;
use Illuminate\Database\Seeder;

/**
 * Seeds the membership types the SSMM PPRC plugin defined for the legacy
 * WordPress site, keeping slugs stable so future member imports match.
 * Prices are placeholder starting values — the committee will update them
 * live from Filament once the platform is deployed.
 */
class MembershipTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'slug' => 'full-member',
                'name' => 'Full Member',
                'description' => 'Annual full membership for adult shooters.',
                'price_cents' => 150000,
                'counts_as_member' => true,
                'allows_sub_members' => true,
                'allowed_sub_member_type_slugs' => ['junior', 'spouse'],
                'sort_order' => 10,
            ],
            [
                'slug' => 'junior',
                'name' => 'Junior',
                'description' => 'Junior shooter under 18, must be linked to an adult member. Free while the parent membership is active.',
                'price_cents' => 0,
                'counts_as_member' => false,
                'is_sub_membership' => true,
                'free_while_linked_adult_active' => true,
                'max_per_parent' => 4,
                'has_age_requirement' => true,
                'age_requirement_type' => AgeRequirementType::Under->value,
                'age_max' => 18,
                'sort_order' => 20,
            ],
            [
                'slug' => 'spouse',
                'name' => 'Spouse',
                'description' => 'Spouse of an existing full member. Separate membership fee applies.',
                'price_cents' => 90000,
                'counts_as_member' => true,
                'is_sub_membership' => true,
                'free_while_linked_adult_active' => false,
                'max_per_parent' => 1,
                'sort_order' => 30,
            ],
            [
                'slug' => 'life-member',
                'name' => 'Life Member',
                'description' => 'Honorary life membership awarded by the committee.',
                'price_cents' => 0,
                'duration_months' => 1200,
                'requires_manual_approval' => true,
                'show_on_registration' => false,
                'counts_as_member' => true,
                'sort_order' => 40,
            ],
            [
                'slug' => 'pensioner',
                'name' => 'Pensioner',
                'description' => 'Reduced rate for pensioners aged 65+.',
                'price_cents' => 90000,
                'counts_as_member' => true,
                'has_age_requirement' => true,
                'age_requirement_type' => AgeRequirementType::AtLeast->value,
                'age_min' => 65,
                'sort_order' => 50,
            ],
        ];

        foreach ($types as $attrs) {
            MembershipType::updateOrCreate(
                ['slug' => $attrs['slug']],
                array_merge([
                    'duration_months' => 12,
                    'is_active' => true,
                    'show_on_registration' => true,
                    'requires_manual_approval' => true,
                    'assign_membership_number_on_approval' => true,
                    'allows_sub_members' => false,
                    'is_sub_membership' => false,
                    'free_while_linked_adult_active' => false,
                    'max_per_parent' => null,
                    'has_age_requirement' => false,
                ], $attrs),
            );
        }
    }
}
