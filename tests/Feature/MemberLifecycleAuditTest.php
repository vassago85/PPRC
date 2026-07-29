<?php

use App\Enums\MemberLifecycle;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

/**
 * The audit command is the thing we lean on to decide whether the backfill can
 * be trusted against real data, so it has to be right about both answers: quiet
 * when the mapping holds, and loud when it does not.
 */
it('reports a clean bill of health when every member matches the mapping', function () {
    Member::factory()->active()->create();
    Member::factory()->expired()->create();
    Member::factory()->resigned()->create();
    Member::factory()->abandoned()->create();

    $this->artisan('members:lifecycle-audit')
        ->expectsOutputToContain('Every member agrees with the mapping rules.')
        ->assertExitCode(0);
});

it('fails loudly when a member drifted away from the mapping', function () {
    $member = Member::factory()->active()->create();

    // Bypass the model so the legacy mirror cannot quietly repair it.
    DB::table('members')->where('id', $member->id)->update([
        'lifecycle' => MemberLifecycle::Expired->value,
        'status' => 'active',
    ]);

    $this->artisan('members:lifecycle-audit')
        ->expectsOutputToContain('do not match the mapping rules')
        ->assertExitCode(1);
});

it('shows a suspended member splitting on whether their membership is still in date', function () {
    Member::factory()->active()->suspended()->create(['expiry_date' => now()->addMonths(3)]);
    Member::factory()->expired()->suspended()->create(['expiry_date' => now()->subMonths(3)]);

    $this->artisan('members:lifecycle-audit')
        ->expectsOutputToContain('still in date')
        ->assertExitCode(0);
});

it('counts the flags that carry what the old column could not', function () {
    Member::factory()->count(2)->active()->suspended()->create(['expiry_date' => now()->addMonth()]);
    Member::factory()->abandoned()->create();

    $this->artisan('members:lifecycle-audit')
        ->expectsOutputToContain('suspended_at set')
        ->assertExitCode(0);
});

it('writes nothing', function () {
    $member = Member::factory()->active()->create();
    $before = DB::table('members')->where('id', $member->id)->first();

    expect($before)->not->toBeNull();

    $this->artisan('members:lifecycle-audit')->assertExitCode(0);

    expect(DB::table('members')->where('id', $member->id)->first())->toEqual($before);
});

it('survives an empty members table', function () {
    $this->artisan('members:lifecycle-audit')
        ->expectsOutputToContain('no members to audit')
        ->assertExitCode(0);
});
