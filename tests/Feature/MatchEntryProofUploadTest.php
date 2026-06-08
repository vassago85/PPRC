<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Livewire\Portal\MyRegistrations;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MatchFormat;
use App\Models\Member;
use App\Models\User;
use App\Support\MediaDisk;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    MediaDisk::flush();
});

function makeUpcomingMatchEntry(): array
{
    $format = MatchFormat::firstOrCreate(
        ['slug' => 'prs-centerfire'],
        ['name' => 'PRS Centerfire', 'short_name' => 'PRS', 'is_active' => true],
    );

    $event = Event::create([
        'match_format_id' => $format->id,
        'title' => 'Centerfire Club Match',
        'start_date' => now()->addWeek()->toDateString(),
        'status' => EventStatus::Published,
        'member_price_cents' => 15000,
        'non_member_price_cents' => 20000,
    ]);

    $user = User::factory()->create();
    $member = Member::factory()->create(['user_id' => $user->id]);

    $entry = EventRegistration::create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'status' => EventRegistrationStatus::Registered,
        'registered_at' => now(),
    ]);

    return [$user, $entry];
}

it('lets a member upload proof of payment for a match entry', function () {
    $disk = MediaDisk::name();
    Storage::fake($disk);
    [$user, $entry] = makeUpcomingMatchEntry();

    expect($entry->awaitingPayment())->toBeTrue();

    Livewire::actingAs($user)
        ->test(MyRegistrations::class)
        ->set("proofUploads.{$entry->id}", UploadedFile::fake()->create('proof.pdf', 200, 'application/pdf'))
        ->call('uploadProof', $entry->id)
        ->assertHasNoErrors();

    $entry->refresh();

    expect($entry->payment_proof_path)->not->toBeNull();
    expect($entry->proof_submitted_at)->not->toBeNull();
    expect($entry->hasUnverifiedProof())->toBeTrue();
    Storage::disk($disk)->assertExists($entry->payment_proof_path);
});

it('rejects a non-file proof upload', function () {
    Storage::fake(MediaDisk::name());
    [$user, $entry] = makeUpcomingMatchEntry();

    Livewire::actingAs($user)
        ->test(MyRegistrations::class)
        ->set("proofUploads.{$entry->id}", 'not-a-file')
        ->call('uploadProof', $entry->id)
        ->assertHasErrors("proofUploads.{$entry->id}");

    expect($entry->fresh()->payment_proof_path)->toBeNull();
});

it('only lets a member upload proof for their own entry', function () {
    Storage::fake(MediaDisk::name());
    [, $entry] = makeUpcomingMatchEntry();

    $otherUser = User::factory()->create();
    Member::factory()->create(['user_id' => $otherUser->id]);

    $attempt = fn () => Livewire::actingAs($otherUser)
        ->test(MyRegistrations::class)
        ->set("proofUploads.{$entry->id}", UploadedFile::fake()->create('proof.pdf', 200, 'application/pdf'))
        ->call('uploadProof', $entry->id);

    expect($attempt)->toThrow(ModelNotFoundException::class);
    expect($entry->fresh()->payment_proof_path)->toBeNull();
});
