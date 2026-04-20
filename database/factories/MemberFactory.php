<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();

        return [
            'user_id' => User::factory(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'known_as' => null,
            'membership_number' => null,
            'phone_country_code' => '+27',
            'phone_number' => $this->faker->numerify('8########'),
            'address_line1' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'province' => $this->faker->randomElement(['Gauteng', 'Western Cape', 'KwaZulu-Natal']),
            'postal_code' => $this->faker->postcode(),
            'country' => 'South Africa',
            'date_of_birth' => $this->faker->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'shooting_disciplines' => ['PRS'],
            'status' => 'pending',
        ];
    }
}
