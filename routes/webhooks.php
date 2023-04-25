<?php

use Illuminate\Support\Facades\Route;
use mindtwo\LaravelPlatformManager\Http\Controllers\WebhookController;
use mindtwo\LaravelPlatformManager\Middleware\EnsureWebhooksAreEnabled;
use mindtwo\LaravelPlatformManager\Middleware\PlatformAuthentication;

/**
 * Routes for documents generator package
 */
Route::name('webhooks.')->group(function () {
    $url = config('platform-resolver.webhooks.endpoint', '/v1/webhooks');

    Route::get($url, [WebhookController::class, 'index'])->middleware([EnsureWebhooksAreEnabled::class]);
    Route::post($url, [WebhookController::class, 'store'])->middleware([EnsureWebhooksAreEnabled::class, PlatformAuthentication::class.':secret']);
});
