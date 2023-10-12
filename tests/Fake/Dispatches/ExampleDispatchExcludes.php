<?php

namespace mindtwo\LaravelPlatformManager\Tests\Fake\Dispatches;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use mindtwo\LaravelPlatformManager\Webhooks\Dispatch;

class ExampleDispatchExcludes extends Dispatch
{

    /**
     * Exclude fields from logging in database.
     *
     * @var array<string>
     */
    protected array $excludeFromLog = [
        'another_payload_str',
    ];

    public function __construct(
        private int $number = 1,
        private string $payloadStr,
        private string $anotherPayloadStr,
    ) {
    }

    /**
     * Handle the response from the webhook.
     */
    public function onResult(array $payload): void
    {

    }

    /**
     * Handle the webhook payload after validation.
     */
    public function requestPayload(): array|JsonSerializable|Arrayable
    {
        return [
            'number' => $this->number,
            'payload_str' => $this->payloadStr,
            'another_payload_str' => $this->anotherPayloadStr,
        ];
    }

}
