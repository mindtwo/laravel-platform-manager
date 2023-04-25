<?php

namespace mindtwo\LaravelPlatformManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use mindtwo\LaravelPlatformManager\Models\Platform;
use mindtwo\LaravelPlatformManager\Models\WebhookRequest;

class WebhookReceivedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public WebhookRequest $webhookRequest,
        public Platform $platform,
    ) {
    }
}
