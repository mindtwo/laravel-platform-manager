<?php

namespace mindtwo\LaravelPlatformManager\Providers;

use Illuminate\Support\ServiceProvider;
use mindtwo\LaravelPlatformManager\Services\WebhookResolver;
use mindtwo\LaravelPlatformManager\Webhooks\Webhook;

class WebhookServiceProvider extends ServiceProvider
{

    /**
     * Webhook classes to register.
     *
     * @var array<string, class-string<Webhook>>
     */
    protected $webhooks = [
        // 'webhook-name' => Webhook::class,
    ];

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->app->singleton(WebhookResolver::class, function ($app) {
            return new WebhookResolver($this->webhooks);
        });
    }

    /**
     * Register the webhook classes.
     */
    protected function registerWebhooks(): void
    {
        // TODo auto discover webhooks if enabled
    }

}
