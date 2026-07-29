<?php

namespace Database\Factories;

use App\Enums\MemberLifecycle;
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
            'lifecycle' => MemberLifecycle::Pending,
        ];
    }

    public function active(?string $expiryDate = null): static
    {
        return $this->state(fn () => [
            'lifecycle' => MemberLifecycle::Active,
            'join_date' => now()->subYear()->toDateString(),
            'expiry_date' => $expiryDate ?? now()->addMonths(6)->toDateString(),
        ]);
    }

    public function expired(?string $expiryDate = null): static
    {
        return $this->state(fn () => [
            'lifecycle' => MemberLifecycle::Expired,
            'join_date' => now()->subYears(2)->toDateString(),
            'expiry_date' => $expiryDate ?? now()->subMonth()->toDateString(),
        ]);
    }

    public function resigned(): static
    {
        return $this->state(fn () => [
            'lifecycle' => MemberLifecycle::Resigned,
            'resigned_at' => now(),
        ]);
    }

    /** A suspension sits on top of whatever lifecycle the member is in. */
    public function suspended(): static
    {
        return $this->state(fn () => ['suspended_at' => now()]);
    }

    /** A stale signup we gave up chasing: still Pending, out of the inbox. */
    public function abandoned(): static
    {
        return $this->state(fn () => [
            'lifecycle' => MemberLifecycle::Pending,
            'abandoned_at' => now(),
            'signup_reminder_sent_at' => now()->subMonth(),
        ]);
    }

    /** Registered but never clicked the link in the verification email. */
    public function awaitingEmail(): static
    {
        return $this->state(fn () => [
            'lifecycle' => MemberLifecycle::Pending,
            // A fresh factory per member; `for()` would share one user across
            // them all and trip the unique constraint on members.user_id.
            'user_id' => User::factory()->unverified(),
        ]);
    }
}
