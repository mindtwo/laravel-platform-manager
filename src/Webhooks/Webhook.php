<?php

namespace mindtwo\LaravelPlatformManager\Webhooks;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JsonSerializable;

abstract class Webhook
{
    /**
     * Hook name for registering the webhook.
     * The name is used to call this webhook from the external platform.
     *
     * @var ?string
     */
    protected ?string $name = null;

    /**
     * Exclude fields from logging in database.
     *
     * @var array<string>
     */
    public array $excludeFromLog = [];

    /**
     * Handle the webhook payload after validation.
     */
    abstract public function handle(array $payload): array|Arrayable|JsonSerializable;

    /**
     * Get the webhook hook name.
     */
    public function name(): string
    {
        if ($this->name !== null) {
            return $this->name;
        }

        return Str::of(static::class)->afterLast('\\')->replace('Webhook', '')->kebab()->__toString();
    }

    /**
     * Rules used to validate the webhook payload.
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Handle webhook errors.
     */
    public function onError(\Throwable $th): array|Arrayable|JsonSerializable
    {
        throw $th;
    }
}
