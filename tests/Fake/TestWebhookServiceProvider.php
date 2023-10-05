<?php

namespace mindtwo\LaravelPlatformManager\Tests\Fake;

use mindtwo\LaravelPlatformManager\Providers\WebhookServiceProvider;

class TestWebhookServiceProvider extends WebhookServiceProvider
{
    /**
     * Webhook classes to register.
     *
     * @var array<string|int, class-string<Webhook>>
     */
    protected $webhooks = [
        Webhooks\ExampleWebhook::class,
        'example-sync' => Webhooks\ExampleSyncWebhook::class,
    ];
}
