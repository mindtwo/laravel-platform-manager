<?php

namespace mindtwo\LaravelPlatformManager\Services;

use Illuminate\Http\Exceptions\HttpResponseException;
use mindtwo\LaravelPlatformManager\Webhooks\Webhook;

class WebhookResolver
{

    /**
     * List of available webhooks.
     *
     * @var array<string, class-string<Webhook>>
     */
    private array $webhooks = [
        // 'webhook-name' => Webhook::class,
    ];

    public function __construct(array $webhooks = [])
    {
        $this->webhooks = $webhooks;
    }

    /**
     * Resolve a webhook.
     */
    public function resolve(string $webhook): Webhook
    {
        $hook = $this->webhooks[$webhook] ?? null;

        if (is_null($hook) || !is_a($hook, Webhook::class, true)) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Webhook not found.',
                ], 404)
            );
        }

        return app()->make($hook);
    }
}
