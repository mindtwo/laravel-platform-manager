<?php

use Illuminate\Support\Facades\Route;
use mindtwo\LaravelPlatformManager\Http\Controllers\CallbackWebhookController;
use mindtwo\LaravelPlatformManager\Http\Controllers\HandleWebhookController;
use mindtwo\LaravelPlatformManager\Http\Controllers\WebhookController;
use mindtwo\LaravelPlatformManager\Middleware\EnsureWebhooksAreEnabled;
use mindtwo\LaravelPlatformManager\Middleware\PlatformAuthentication;

/**
 * Routes for documents generator package
 */
Route::name('webhooks.')->group(function () {
    // v2 webhook routes
    Route::post('/v2/webhooks', HandleWebhookController::class)->middleware([EnsureWebhooksAreEnabled::class, PlatformAuthentication::class.':secret'])->name('v2');

    Route::post('/v2/callback', CallbackWebhookController::class)->name('v2.callback');
});
