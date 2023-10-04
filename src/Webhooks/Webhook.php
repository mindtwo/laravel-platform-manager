<?php

namespace mindtwo\LaravelPlatformManager\Webhooks;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

abstract class Webhook
{

    /**
     * Handle the webhook payload after validation.
     *
     * @param array $payload
     * @return array|Arrayable|JsonSerializable
     */
    public abstract function handle(array $payload): array|Arrayable|JsonSerializable;


    /**
     * Rules used to validate the webhook payload.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Handle webhook errors.
     *
     * @param \Throwable $th
     * @return array|Arrayable|JsonSerializable
     */
    public function onError(\Throwable $th): array
    {
        throw $th;
    }

}
