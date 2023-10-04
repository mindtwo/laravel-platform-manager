<?php

namespace mindtwo\LaravelPlatformManager\Webhooks;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JsonSerializable;

abstract class Dispatch
{

    protected bool $isSync = false;

    /**
     * Handle the response from the webhook.
     */
    abstract public function onResult(array $payload): void;

    /**
     * Handle the webhook payload after validation.
     */
    abstract public function requestPayload(): array|JsonSerializable|Arrayable;

    /**
     * Get the webhook hook name.
     *
     * @return string
     */
    public function hook(): string
    {
        return Str::of(static::class)->replace('Dispatch', '')->kebab()->__toString();
    }

    public function isSync(): bool
    {
        return $this->isSync;
    }

    /**
     * Handle webhook errors.
     */
    public function onError(\Throwable $th): void
    {
        throw $th;
    }
}
