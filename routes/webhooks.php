<?php

use Illuminate\Support\Facades\Route;
use mindtwo\LaravelPlatformManager\Http\Controllers\HandleV2WebhookController;
use mindtwo\LaravelPlatformManager\Http\Controllers\WebhookController;
use mindtwo\LaravelPlatformManager\Middleware\EnsureWebhooksAreEnabled;
use mindtwo\LaravelPlatformManager\Middleware\PlatformAuthentication;

/**
 * Routes for documents generator package
 */
Route::name('webhooks.')->group(function () {
    $url = config('platform-resolver.webhooks.endpoint', '/v1/webhooks');

    if (config('app.env') == 'testing') {
        Route::get($url, [WebhookController::class, 'index'])->middleware([EnsureWebhooksAreEnabled::class]);
    }

    Route::post($url, [WebhookController::class, 'store'])->middleware([EnsureWebhooksAreEnabled::class, PlatformAuthentication::class.':secret']);

    Route::post('/v2/webhooks', HandleV2WebhookController::class)->middleware([EnsureWebhooksAreEnabled::class, PlatformAuthentication::class.':secret'])->name('v2');
});
