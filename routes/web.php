<?php

use App\Http\Controllers\Webhooks\PaystackWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::post('/webhooks/paystack', PaystackWebhookController::class)
    ->name('webhooks.paystack');
