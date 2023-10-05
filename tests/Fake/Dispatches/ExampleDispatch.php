<?php

namespace mindtwo\LaravelPlatformManager\Tests\Fake\Dispatches;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use mindtwo\LaravelPlatformManager\Webhooks\Dispatch;

class ExampleDispatch extends Dispatch
{

    public function __construct(
        private int $number = 1,
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
        ];
    }

}
