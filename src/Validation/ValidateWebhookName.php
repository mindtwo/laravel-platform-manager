<?php

namespace mindtwo\LaravelPlatformManager\Validation;

use mindtwo\LaravelPlatformManager\Models\Webhook;
use mindtwo\LaravelPlatformManager\Services\PlatformResolver;

class ValidateWebhookName
{
    /**
     * Validate if webhook is available.
     *
     * @return void
     */
    public function __invoke(string $hook)
    {
        $availableHooks = array_keys(config('webhooks'));

        if (! in_array($hook, $availableHooks)) {
            abort(404, 'No hook found');
        }
        $platformResolver = app()->make(PlatformResolver::class);

        $webhook = Webhook::query()->where([
            'hook' => $hook,
            'platform_id' => $platformResolver->getCurrentPlatform()->id,
        ])->first();

        if ($webhook !== null && ! $webhook->active) {
            abort(404, 'No hook found');
        }
    }
}
