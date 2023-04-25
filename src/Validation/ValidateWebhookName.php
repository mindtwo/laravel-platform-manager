<?php

namespace mindtwo\LaravelPlatformManager\Validation;

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
    }
}
