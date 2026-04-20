<?php

use App\Http\Controllers\Site\AnnouncementController;
use App\Http\Controllers\Site\ExcoController;
use App\Http\Controllers\Site\FaqController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Webhooks\PaystackWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/news', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/news/{slug}', [AnnouncementController::class, 'show'])->name('announcements.show');

Route::get('/exco', ExcoController::class)->name('exco');
Route::get('/committee', ExcoController::class);
Route::get('/faqs', FaqController::class)->name('faqs');

Route::view('/events', 'site.stubs.events')->name('events');
Route::view('/results', 'site.stubs.results')->name('results');
Route::view('/gallery', 'site.stubs.gallery')->name('gallery');

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
