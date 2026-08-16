<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Enums\MemberLifecycle;
use App\Enums\MembershipStatus;
use App\Models\Event;
use App\Models\MatchFormat;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Database\Seeders\MembershipTypesSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds the deterministic fixture the family-household Playwright smoke test
 * needs: an active adult with a known password + one published, open match
 * ready to enter. Idempotent — safe to re-run.
 *
 * Not for production. The command aborts unless APP_ENV is local/testing so a
 * misplaced deploy can never mint a login on the live site.
 */
class SeedE2eHousehold extends Command
{
    protected $signature = 'e2e:seed-household
                            {--email=e2e-parent@example.test : Email of the parent account}
                            {--password=Password123! : Password for the parent account}
                            {--match-slug=e2e-family-match : Slug of the open match to seed}';

    protected $description = 'Seed an active adult and an open match for the family-household Playwright smoke test.';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Refusing to seed E2E fixtures outside local/testing.');

            return self::FAILURE;
        }

        $this->ensureMembershipTypes();

        $email = (string) $this->option('email');
        $password = (string) $this->option('password');
        $matchSlug = (string) $this->option('match-slug');

        [$user, $member] = $this->ensureActiveAdult($email, $password);
        $event = $this->ensureOpenMatch($matchSlug);

        $this->info(sprintf(
            'Seeded parent %s (member #%d) and open match "%s" (id %d).',
            $user->email,
            $member->id,
            $event->title,
            $event->id,
        ));

        return self::SUCCESS;
    }

    protected function ensureMembershipTypes(): void
    {
        if (! MembershipType::where('slug', 'junior')->exists()) {
            $this->call('db:seed', ['--class' => MembershipTypesSeeder::class, '--force' => true]);
        }
    }

    /**
     * @return array{0: User, 1: Member}
     */
    protected function ensureActiveAdult(string $email, string $password): array
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'E2E Parent',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'created_via_import' => false,
            ],
        );

        // Force the password / verification on every run so tests are stable
        // across schema/env changes.
        $user->forceFill([
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ])->save();

        $member = Member::firstOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'E2E',
                'last_name' => 'Parent',
                'lifecycle' => MemberLifecycle::Active,
                'join_date' => now()->startOfYear(),
            ],
        );

        // Wipe existing sub-members so the "add a junior" flow always starts
        // from an empty family list — the smoke test asserts on that state.
        foreach ($member->subMembers()->get() as $sub) {
            $sub->memberships()->delete();
            $sub->user()->delete();
            $sub->forceDelete();
        }

        Membership::where('member_id', $member->id)->delete();

        $type = MembershipType::where('slug', 'full-member')->firstOrFail();

        Membership::create([
            'member_id' => $member->id,
            'membership_type_id' => $type->id,
            'period_start' => now()->startOfYear(),
            'period_end' => now()->endOfYear(),
            'status' => MembershipStatus::Active,
            'price_cents_snapshot' => $type->price_cents,
            'membership_type_slug_snapshot' => $type->slug,
            'membership_type_name_snapshot' => $type->name,
        ]);

        return [$user->fresh(), $member->fresh()];
    }

    protected function ensureOpenMatch(string $slug): Event
    {
        $format = MatchFormat::firstOrCreate(
            ['slug' => 'prs-centerfire'],
            ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
        );

        $event = Event::where('slug', $slug)->first();

        $attrs = [
            'match_format_id' => $format->id,
            'title' => 'Family Smoke Match',
            'slug' => $slug,
            'start_date' => now()->addWeek()->toDateString(),
            'status' => EventStatus::Published,
            'published_at' => now()->subDay(),
            'registrations_open' => true,
            'registration_require_division' => false,
            'registration_require_category' => false,
            'member_price_cents' => 20000,
            'non_member_price_cents' => 30000,
            'junior_price_cents' => 10000,
        ];

        if ($event) {
            $event->forceFill($attrs)->save();

            return $event->fresh();
        }

        return Event::create(array_merge(['slug' => $slug], $attrs));
    }
}
