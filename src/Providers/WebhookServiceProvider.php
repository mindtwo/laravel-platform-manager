<?php

namespace mindtwo\LaravelPlatformManager\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use mindtwo\LaravelPlatformManager\Services\DispatchHandlerService;
use mindtwo\LaravelPlatformManager\Services\WebhookResolver;
use mindtwo\LaravelPlatformManager\Webhooks\Webhook;

class WebhookServiceProvider extends ServiceProvider
{

    /**
     * Webhook classes to register.
     *
     * @var array<string|int, class-string<Webhook>>
     */
    protected $webhooks = [
        // 'webhook-name' => Webhook::class,
        // Webhook::class,
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
            return new WebhookResolver($this->registerWebhooks());
        });

        $this->app->singleton(DispatchHandlerService::class, function ($app) {
            return new DispatchHandlerService();
        });
    }

    /**
     * Register the webhook classes.
     */
    protected function registerWebhooks(): array
    {
        // TODo auto discover webhooks if enabled

        $hooks = [];

        foreach (array_flip($this->webhooks) as $webhook => $name) {
            if (!is_a($webhook, Webhook::class, true)) {
                Log::error('Webhook ' . $name . ' is not a valid webhook class. Skipping...');
                continue;
            }

            $name = is_string($name) ? $name : (new $webhook)->name();
            $hooks[$name] = $webhook;
        }

        return $hooks;
    }

}
