<?php

use App\Enums\MemberLifecycle;
use App\Enums\MembershipStatus;
use App\Models\Member;
use App\Models\Membership;
use App\Models\User;
use App\Services\Membership\SubMemberRegistrar;
use Database\Seeders\MembershipTypesSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(MembershipTypesSeeder::class);
});

/** An adult parent holding a current dated membership the junior can inherit. */
function parentWithMembership(?Carbon $periodEnd = null): Member
{
    $parent = refMember('Adult', 'Parent');

    Membership::factory()->create([
        'member_id' => $parent->id,
        'status' => MembershipStatus::Active,
        'period_start' => now()->startOfYear(),
        'period_end' => $periodEnd ?? now()->endOfYear(),
    ]);

    return $parent->refresh();
}

function registerJunior(Member $parent, array $overrides = []): Member
{
    return app(SubMemberRegistrar::class)->registerJunior($parent, array_merge([
        'first_name' => 'Junior',
        'last_name' => 'Parent',
        'date_of_birth' => now()->subYears(12)->toDateString(),
    ], $overrides));
}

it('gives a junior the same structure as any other member: an account and a real membership', function () {
    $parent = parentWithMembership();

    $junior = registerJunior($parent);

    expect($junior->user)->not->toBeNull()
        ->and($junior->linked_adult_member_id)->toBe($parent->id)
        ->and($junior->currentMembership())->not->toBeNull()
        ->and($junior->currentMembership()->membership_type_slug_snapshot)->toBe('junior')
        // A free junior under an active parent is active straight away.
        ->and($junior->fresh()->lifecycle)->toBe(MemberLifecycle::Active);
});

it('provisions a managed placeholder account when no email is given', function () {
    $parent = parentWithMembership();

    $junior = registerJunior($parent);

    expect($junior->user->email)->toEndWith('@members.pretoriaprc.co.za')
        // Managed accounts are flagged so the verification email never fires at
        // a mailbox that does not exist.
        ->and($junior->user->created_via_import)->toBeTrue();
});

it('gives the junior their own login when a contact email is provided', function () {
    $parent = parentWithMembership();

    $junior = registerJunior($parent, ['email' => 'kid@example.com']);

    expect($junior->user->email)->toBe('kid@example.com')
        ->and($junior->user->created_via_import)->toBeFalse();
});

it('refuses an email that already belongs to a member', function () {
    $parent = parentWithMembership();
    $taken = refMember('Someone', 'Else');

    expect(fn () => registerJunior($parent, ['email' => $taken->user->email]))
        ->toThrow(ValidationException::class);
});

it('mirrors the parent\'s membership period so they renew together', function () {
    $parent = parentWithMembership(periodEnd: now()->addMonths(4)->startOfDay());

    $junior = registerJunior($parent);

    expect($junior->currentMembership()->period_end->toDateString())
        ->toBe($parent->currentMembership()->period_end->toDateString());
});

it('puts a lifetime parent\'s junior on their own annual clock instead of an endless term', function () {
    $parent = parentWithMembership(periodEnd: null); // factory needs a value...
    // Force the parent's membership to be a lifetime one (no expiry).
    $parent->currentMembership()->update(['period_end' => null]);
    $parent->refresh();

    $junior = registerJunior($parent);

    $end = $junior->currentMembership()->period_end;

    expect($end)->not->toBeNull()
        ->and($end->isFuture())->toBeTrue()
        // The Junior type runs 12 months, so the term is about a year out.
        ->and($end->lessThanOrEqualTo(now()->addYear()->addDay()))->toBeTrue();
});

it('will not register someone who is too old to be a junior', function () {
    $parent = parentWithMembership();

    expect(fn () => registerJunior($parent, ['date_of_birth' => now()->subYears(25)->toDateString()]))
        ->toThrow(ValidationException::class);

    // The rejected junior must not leave a stray user account behind.
    expect(User::where('email', 'like', 'junior-%@members.pretoriaprc.co.za')->count())->toBe(0);
});

it('creates a junior membership the age-out job can later expire', function () {
    $parent = parentWithMembership();
    $junior = registerJunior($parent);

    // They cross 18 down the line.
    $junior->update(['date_of_birth' => now()->subYears(19)->toDateString()]);

    $this->artisan('memberships:age-sub-members')->assertExitCode(0);

    expect($junior->currentMembership())->toBeNull()
        ->and(Membership::where('member_id', $junior->id)->first()->status)
        ->toBe(MembershipStatus::Expired);
});
