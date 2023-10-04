<?php

namespace mindtwo\LaravelPlatformManager\Webhooks\Handler;

use Illuminate\Support\Facades\Validator;

trait ValidatesPayload
{
    /**
     * Validate the webhook payload.
     */
    protected function validatePayload(): array
    {
        $validator = Validator::make($this->payload, $this->webhook->rules());

        return $validator->validate();
    }
}
