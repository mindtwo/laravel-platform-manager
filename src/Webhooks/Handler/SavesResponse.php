<?php

namespace mindtwo\LaravelPlatformManager\Webhooks\Handler;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

trait SavesResponse
{
    /**
     * Validate the webhook payload.
     */
    protected function saveWebhookResponse(array|Arrayable|JsonSerializable $result = []): void
    {
        if (! property_exists($this, 'request')) {
            return;
        }

        $this->request->response()->create([
            'payload' => $result,
            'ulid' => $this->request->ulid,
            'hook' => $this->request->hook,
        ]);
    }
}
