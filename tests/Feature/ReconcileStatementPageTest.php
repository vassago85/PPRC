<?php

use App\Enums\MatchPaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Pages\ReconcileStatement;
use App\Models\User;
use App\Services\Payments\StatementReconciliation as Recon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Mail::fake();
    Cache::forget('site_settings:payments.bank.reference_prefix');

    $this->treasurer = User::factory()->create(['email_verified_at' => now()]);
    $this->treasurer->assignRole('treasurer');
    $this->actingAs($this->treasurer);
});

/**
 * A statement in the club's real export format, carrying one line of each kind
 * the resolver has to deal with.
 */
function statementCsv(): string
{
    return <<<'CSV'
    ACCOUNT TRANSACTION HISTORY

    Name:, Test, Club
    Account:, 00000000000, [Gold Business Account]
    Balance:, 2250.00, 2250.00

    Date, Amount, Balance, Description
    2026/07/28, 450.00, 2250.00, ABSA BANK PPRC-M14-103
    2026/07/28, 450.00, 1800.00, PPRCM1489
    2026/07/28, 450.00, 1350.00, ABSA BANK PPRC-20260105-0006
    2026/07/27, 450.00, 900.00, M BRUMMER
    2026/07/27, 450.00, 450.00, CAPITEC   PPRC-M14-77
    2026/07/26, 450.00, 0.00, ABSA BANK PPRC-M99-4242
    2026/07/11, -96.00, 0.00, #MONTHLY ACCOUNT FEE
    2026/07/07, -15000.00, 96.00, INTERNAL TRF
    CSV;
}

function uploadStatement(?string $csv = null): Testable
{
    return Livewire::test(ReconcileStatement::class)
        ->set('file', UploadedFile::fake()->createWithContent('statement.csv', $csv ?? statementCsv()));
}

/** Everything the sample statement above needs to resolve against. */
function seedStatementFixtures(): array
{
    $event14 = refEvent(14, 'Match Fourteen');

    // Clean hits: the reference resolves to one unpaid entry for exactly R450.
    $clean = refEntry(103, $event14, refMember('Jaco', 'Smit'));
    $mangled = refEntry(89, $event14, refMember('Wynand', 'Louw'));

    // Reused reference: paid with the reference they were given when they joined.
    $reuser = refMember('Marius', 'Kruger');
    refMembershipPayment($reuser, 'PPRC-20260105-0006', PaymentStatus::Confirmed);
    $reused = refEntry(104, $event14, $reuser);

    // Guest reachable only by the surname the bank left behind.
    $guest = refEntry(200, $event14, null, [
        'guest_name' => 'Marius Brummer',
        'guest_email' => 'marius@example.com',
    ]);

    // Already banked before the statement was pulled.
    $paid = refEntry(77, $event14, refMember('Kobus', 'Venter'), ['paid_at' => now()->subDay()]);

    return compact('clean', 'mangled', 'reused', 'guest', 'paid');
}

it('renders for a committee member who handles money', function () {
    Livewire::test(ReconcileStatement::class)
        ->assertOk()
        ->assertSee('Bank statement export');
});

it('reads the statement and sorts every line into the right pile', function () {
    seedStatementFixtures();

    $page = uploadStatement();

    $summary = $page->get('summary');

    expect($summary['lines'])->toBe(6)
        // Only the two clean references clear the bar for settling in bulk.
        ->and($summary['ready'])->toBe(2)
        // The reused reference and the guest surname both need a person to look.
        ->and($summary['review'])->toBe(2)
        ->and($summary['settled'])->toBe(1)
        ->and($summary['unmatched'])->toBe(1)
        ->and($summary['total_cents'])->toBe(270000);
});

it('ignores the fees and transfers out', function () {
    seedStatementFixtures();

    expect(uploadStatement()->get('skipped'))->toBe(['debits' => 2, 'ignored' => 0]);
});

it('settles every certain line in one go and leaves the rest alone', function () {
    $fixtures = seedStatementFixtures();

    uploadStatement()->call('applyAllReady');

    expect($fixtures['clean']->refresh()->paid_at)->not->toBeNull()
        ->and($fixtures['clean']->payment_method)->toBe(MatchPaymentMethod::Eft)
        ->and($fixtures['clean']->marked_paid_by_user_id)->toBe($this->treasurer->id)
        ->and($fixtures['mangled']->refresh()->paid_at)->not->toBeNull();

    // The ambiguous ones must still be waiting for a human.
    expect($fixtures['reused']->refresh()->paid_at)->toBeNull()
        ->and($fixtures['guest']->refresh()->paid_at)->toBeNull();
});

it('reports nothing left to settle in bulk once it has been done', function () {
    seedStatementFixtures();

    $page = uploadStatement()->call('applyAllReady');

    expect($page->get('summary')['ready'])->toBe(0)
        ->and($page->get('summary')['settled'])->toBe(3);
});

it('settles a line the admin picked a candidate for', function () {
    $fixtures = seedStatementFixtures();

    $page = uploadStatement();

    $reused = collect($page->get('reviews'))->firstWhere('description', 'ABSA BANK PPRC-20260105-0006');

    $page->call('apply', $reused['row'], 'match_entry:'.$fixtures['reused']->id);

    expect($fixtures['reused']->refresh()->paid_at)->not->toBeNull();
});

it('shows the settled line as done without touching it', function () {
    $fixtures = seedStatementFixtures();

    $before = $fixtures['paid']->paid_at;

    $page = uploadStatement()->call('applyAllReady');

    expect($fixtures['paid']->refresh()->paid_at->timestamp)->toBe($before->timestamp);

    $line = collect($page->get('reviews'))->firstWhere('description', 'CAPITEC   PPRC-M14-77');

    expect($line['status'])->toBe(Recon::SETTLED);
});

it('refuses to settle a line twice', function () {
    $fixtures = seedStatementFixtures();

    $page = uploadStatement()->call('applyAllReady');

    $paidAt = $fixtures['clean']->refresh()->paid_at;
    $line = collect($page->get('reviews'))->firstWhere('description', 'ABSA BANK PPRC-M14-103');

    $page->call('apply', $line['row'], 'match_entry:'.$fixtures['clean']->id);

    expect($fixtures['clean']->refresh()->paid_at->timestamp)->toBe($paidAt->timestamp);
});

it('confirms a membership payment straight off the statement', function () {
    $member = refMember('Coenie', 'van Tonder');
    $payment = refMembershipPayment($member, 'PPRC-20260725-0001', PaymentStatus::Submitted, 40000);

    $csv = "Date, Amount, Balance, Description\n2026/07/27, 400.00, 400.00, AF BANK   PPRC202607250001";

    uploadStatement($csv)->call('applyAllReady');

    expect($payment->refresh()->status)->toBe(PaymentStatus::Confirmed);
});

it('says plainly when the file is not a statement', function () {
    uploadStatement("just,some,numbers\n1,2,3")
        ->assertSet('parsed', false);
});

it('keeps the page away from members with no money permissions', function () {
    $shooter = User::factory()->create(['email_verified_at' => now()]);
    $shooter->assignRole('member');
    $this->actingAs($shooter);

    expect(ReconcileStatement::canAccess())->toBeFalse();
});

it('surfaces the payer name left in the narration so an admin can recognise a line', function () {
    seedStatementFixtures();

    $reviews = collect(uploadStatement()->get('reviews'));

    // "M BRUMMER" carries no reference at all — the name is all there is to
    // go on, so it must come through even though the app has no clean match.
    $brummer = $reviews->firstWhere('description', 'M BRUMMER');

    expect($brummer['payer'])->toContain('BRUMMER');
});

it('sets a recognised line aside and can bring it back', function () {
    seedStatementFixtures();

    $page = uploadStatement();
    $row = collect($page->get('reviews'))->firstWhere('description', 'M BRUMMER')['row'];

    $page->call('ignore', $row);

    // Gone from the working list, parked in the ignored pile.
    expect(collect($page->instance()->visibleReviews())->pluck('description'))
        ->not->toContain('M BRUMMER')
        ->and($page->instance()->ignoredCount())->toBe(1);

    $page->set('filter', 'ignored');
    expect(collect($page->instance()->visibleReviews())->pluck('description'))
        ->toContain('M BRUMMER');

    $page->call('restore', $row)->set('filter', 'all');
    expect(collect($page->instance()->visibleReviews())->pluck('description'))
        ->toContain('M BRUMMER');
});

it('sets aside every line currently filtered on screen in one go', function () {
    seedStatementFixtures();

    $page = uploadStatement()->set('filter', Recon::UNMATCHED);

    $page->call('ignoreAllVisible');

    expect($page->instance()->ignoredCount())->toBe(1)
        ->and($page->set('filter', Recon::UNMATCHED)->instance()->visibleReviews())->toBe([]);
});

it('leaves an ignored ready line out of the bulk settle', function () {
    $fixtures = seedStatementFixtures();

    $page = uploadStatement();
    $row = collect($page->get('reviews'))->firstWhere('description', 'ABSA BANK PPRC-M14-103')['row'];

    $page->call('ignore', $row)->call('applyAllReady');

    // The ignored ready line is untouched; the other ready line still settles.
    expect($fixtures['clean']->refresh()->paid_at)->toBeNull()
        ->and($fixtures['mangled']->refresh()->paid_at)->not->toBeNull();
});

it('will not settle entries for someone without registration rights', function () {
    $fixtures = seedStatementFixtures();

    // Reaches the page on payments.view alone, but must not be able to touch
    // match entries — not one at a time, and not in bulk.
    $viewer = User::factory()->create(['email_verified_at' => now()]);
    $viewer->givePermissionTo('payments.view');
    $this->actingAs($viewer);

    uploadStatement()->call('applyAllReady');

    expect($fixtures['clean']->refresh()->paid_at)->toBeNull()
        ->and($fixtures['mangled']->refresh()->paid_at)->toBeNull();
});
