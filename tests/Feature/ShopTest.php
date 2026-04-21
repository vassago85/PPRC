<?php

use App\Enums\ShopRunStatus;
use App\Mail\ShopWaitlistConfirm;
use App\Models\Member;
use App\Models\ShopRun;
use App\Models\ShopWaitlistSubscriber;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('confirms waitlist from token', function () {
    Mail::fake();

    $sub = ShopWaitlistSubscriber::query()->create([
        'email' => 'wait@example.com',
        'name' => 'Tester',
        'confirm_token' => 'confirmtokentest123456789012345678901234567890',
        'unsubscribe_token' => 'unsubtokentest123456789012345678901234567890',
    ]);

    $this->get(route('shop.waitlist.confirm', ['token' => 'confirmtokentest123456789012345678901234567890']))
        ->assertRedirect(route('shop'));

    expect($sub->fresh()->confirmed_at)->not->toBeNull();
});

it('queues waitlist confirmation email on signup', function () {
    Mail::fake();

    $this->from('/shop')
        ->post(route('shop.waitlist.store'), [
            'email' => 'newwait@example.com',
            'name' => 'New',
        ])
        ->assertRedirect(route('shop'));

    Mail::assertQueued(ShopWaitlistConfirm::class);
});

it('returns 404 for portal checkout when run is not open', function () {
    $user = User::factory()->create();
    Member::factory()->create(['user_id' => $user->id]);

    $run = ShopRun::query()->create([
        'title' => 'Closed run',
        'slug' => 'closed-run',
        'status' => ShopRunStatus::Closed,
        'preview_visible' => false,
    ]);

    $this->actingAs($user)->get(route('portal.shop.run', $run))
        ->assertNotFound();
});
