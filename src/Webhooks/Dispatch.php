<?php

namespace mindtwo\LaravelPlatformManager\Webhooks;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JsonSerializable;

abstract class Dispatch
{

    /**
     * Is the webhook sync or async?
     *
     * @var boolean
     */
    protected bool $isSync = false;

    /**
     * Hook name for registered webhook on external platform.
     *
     * @var ?string
     */
    protected ?string $hook = null;

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
        if ($this->hook !== null) {
            return $this->hook;
        }

        return Str::of(static::class)->afterLast('\\')->replace('Dispatch', '')->kebab()->__toString();
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
