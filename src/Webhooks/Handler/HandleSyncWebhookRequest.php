<?php

namespace mindtwo\LaravelPlatformManager\Webhooks\Handler;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use mindtwo\LaravelPlatformManager\Models\V2\WebhookRequest;
use mindtwo\LaravelPlatformManager\Webhooks\Webhook;

class HandleSyncWebhookRequest
{
    use ValidatesPayload;
    use SavesResponse;

    public function __construct(
        private Webhook $webhook,
        private array $payload,
        private WebhookRequest $request,
    ) {
    }

    /**
     * Handle the webhook and return the result.
     */
    public function handle(): mixed
    {
        try {
            $payload = $this->validatePayload();

            $result = $this->webhook->handle($payload);

            // save result value to database
            $this->saveWebhookResponse($result);

            return $result;
        } catch (\Throwable $th) {
            $error = $this->webhook->onError($th);

            // save error value to database
            $this->saveWebhookResponse($error);
        }

        return null;
    }
}
