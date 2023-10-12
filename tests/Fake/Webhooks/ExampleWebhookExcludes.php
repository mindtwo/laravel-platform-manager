<?php

namespace mindtwo\LaravelPlatformManager\Tests\Fake\Webhooks;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use mindtwo\LaravelPlatformManager\Webhooks\Webhook;

class ExampleWebhookExcludes extends Webhook
{

    /**
     * Exclude fields from logging in database.
     *
     * @var array<string>
     */
    protected array $excludeFromLog = [
        'another_payload_str',
    ];

    /**
     * Handle the response from the webhook.
     */
    public function onResult(array $payload): void
    {

    }

    public function handle(array $payload): array|Arrayable|JsonSerializable
    {
        return [];
    }

}
