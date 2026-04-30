<?php

use App\Http\Controllers\Auth\EmailVerificationPinController;
use App\Http\Controllers\Site\AboutController;
use App\Http\Controllers\Site\AnnouncementController;
use App\Http\Controllers\Site\CertificateController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\FaqController;
use App\Http\Controllers\Portal\EndorsementLetterController;
use App\Http\Controllers\Portal\ParticipationLetterController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\MatchController;
use App\Http\Controllers\Site\MembershipController;
use App\Http\Controllers\Site\ResultController;
use App\Http\Controllers\Site\ShopController;
use App\Http\Controllers\Site\GalleryController;
use App\Http\Controllers\Site\ShopWaitlistController;
use App\Http\Controllers\Webhooks\PaystackWebhookController;
use App\Livewire\Portal\Dashboard;
use App\Livewire\Portal\Documents;
use App\Livewire\Portal\Membership;
use App\Livewire\Portal\MyRegistrations;
use App\Livewire\Portal\MyResults;
use App\Livewire\Portal\ProfileEdit;
use App\Livewire\Portal\ShopCheckout;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/about', AboutController::class)->name('about');
// Legacy committee URLs — point at the committee section on /about so any
// bookmarks / printed material keep working.
Route::redirect('/exco', '/about#committee')->name('exco');
Route::redirect('/committee', '/about#committee');

Route::get('/membership', MembershipController::class)->name('membership');

Route::get('/news', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/news/{slug}', [AnnouncementController::class, 'show'])->name('announcements.show');

Route::get('/faqs', FaqController::class)->name('faqs');

Route::view('/prs-academy', 'site.prs-academy')->name('prs.academy');

Route::get('/matches', [MatchController::class, 'index'])->name('matches');
Route::get('/matches/{event:slug}', [MatchController::class, 'show'])->name('matches.show');
Route::get('/membership/certificate/{token}', [CertificateController::class, 'show'])
    ->where('token', '[a-zA-Z0-9]+')
    ->name('membership.certificate.show');
Route::get('/membership/verify/{token}', [CertificateController::class, 'verify'])
    ->where('token', '[a-zA-Z0-9]+')
    ->name('membership.certificate.verify');
Route::redirect('/events', '/matches');
Route::get('/results', [ResultController::class, 'index'])->name('results');
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');
Route::get('/gallery/{event:slug}', [GalleryController::class, 'show'])->name('gallery.show');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/shop/waitlist/confirm/{token}', [ShopWaitlistController::class, 'confirm'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('shop.waitlist.confirm');
Route::get('/shop/waitlist/unsubscribe/{token}', [ShopWaitlistController::class, 'unsubscribe'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('shop.waitlist.unsubscribe');
Route::post('/shop/waitlist', [ShopWaitlistController::class, 'store'])
    ->middleware('throttle:10,10')
    ->name('shop.waitlist.store');
Route::get('/shop/{run}', [ShopController::class, 'show'])->name('shop.run');

// Contact form (GET to render, POST to send). Throttled to blunt spam bots.
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])
    ->middleware('throttle:5,10')
    ->name('contact.submit');

// Admin endorsement letter preview (committee only)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/endorsements/{endorsement}/preview-letter', [EndorsementLetterController::class, 'preview'])
        ->name('admin.endorsements.preview-letter');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/email/verify', [EmailVerificationPinController::class, 'show'])
        ->name('verification.notice');
    Route::post('/email/verify-pin', [EmailVerificationPinController::class, 'verify'])
        ->middleware('throttle:12,1')
        ->name('verification.pin.verify');
    Route::post('/email/verification-notification', [EmailVerificationPinController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::middleware(['web', 'auth', 'verified'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/membership', Membership::class)->name('membership');
    Route::get('/shop/{run}', ShopCheckout::class)->name('shop.run');
    Route::get('/results', MyResults::class)->name('results');
    Route::get('/registrations', MyRegistrations::class)->name('registrations');
    Route::get('/profile/edit', ProfileEdit::class)->name('profile.edit');
    Route::get('/documents', Documents::class)->name('documents');
    Route::get('/documents/participation', ParticipationLetterController::class)->name('documents.participation');
    Route::get('/documents/endorsement/{token}', EndorsementLetterController::class)
        ->where('token', '[a-zA-Z0-9]+')
        ->name('documents.endorsement');
    Route::view('/account/profile', 'portal.account.profile')->name('account.profile');
    Route::view('/account/password', 'portal.account.password')->name('account.password');
});

Route::post('/webhooks/paystack', PaystackWebhookController::class)
    ->name('webhooks.paystack');
