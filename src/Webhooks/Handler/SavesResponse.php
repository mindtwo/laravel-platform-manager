<?php

namespace mindtwo\LaravelPlatformManager\Webhooks\Handler;

trait SavesResponse
{
    /**
     * Validate the webhook payload.
     */
    protected function saveWebhookResponse(array $result = []): void
    {
        if (! property_exists($this, 'request')) {
            return;
        }

        $this->request->response()->create([
            'payload' => $result,
            'hook' => $this->request->hook,
        ]);
    }
}
