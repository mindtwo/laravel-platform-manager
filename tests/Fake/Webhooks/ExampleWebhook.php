<?php

namespace mindtwo\LaravelPlatformManager\Tests\Fake\Webhooks;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use mindtwo\LaravelPlatformManager\Webhooks\Webhook;

class ExampleWebhook extends Webhook
{
    /**
     * Rules used to validate the webhook payload.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'number' => 'required|integer',
        ];
    }

    /**
     * Handle the webhook payload after validation.
     *
     * @param array $payload
     * @return array|Arrayable|JsonSerializable
     */
    public function handle(array $payload): array|Arrayable|JsonSerializable
    {

        // test doubles the payload value at key number
        return [
            'doubled' => $payload['number'] * 2,
        ];
    }

}
