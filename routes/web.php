<?php

use App\Http\Controllers\Site\AnnouncementController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\ExcoController;
use App\Http\Controllers\Site\FaqController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\MatchController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\ResultController;
use App\Http\Controllers\Webhooks\PaystackWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/news', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/news/{slug}', [AnnouncementController::class, 'show'])->name('announcements.show');

Route::get('/exco', ExcoController::class)->name('exco');
Route::get('/committee', ExcoController::class);
Route::get('/faqs', FaqController::class)->name('faqs');

Route::get('/matches', [MatchController::class, 'index'])->name('matches');
Route::get('/matches/{event:slug}', [MatchController::class, 'show'])->name('matches.show');
Route::redirect('/events', '/matches');
Route::get('/results', [ResultController::class, 'index'])->name('results');
Route::view('/gallery', 'site.stubs.gallery')->name('gallery');
Route::view('/shop', 'site.stubs.shop')->name('shop');

// Contact form (GET to render, POST to send). Throttled to blunt spam bots.
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])
    ->middleware('throttle:5,10')
    ->name('contact.submit');

Route::middleware(['web', 'auth'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/membership', \App\Livewire\Portal\Membership::class)->name('membership');
    Route::redirect('/', '/portal/membership');
});

Route::post('/webhooks/paystack', PaystackWebhookController::class)
    ->name('webhooks.paystack');

// Catch-all for CMS pages. Keep this LAST so existing routes win.
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('pages.show');
