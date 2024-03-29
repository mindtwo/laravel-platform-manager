<?php

namespace mindtwo\LaravelPlatformManager\Webhooks;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JsonSerializable;
use mindtwo\LaravelPlatformManager\Models\Platform;
use mindtwo\LaravelPlatformManager\Traits\ExcludeKeysInLog;

abstract class Webhook
{

    use ExcludeKeysInLog;

    /**
     * Hook name for registering the webhook.
     * The name is used to call this webhook from the external platform.
     *
     * @var ?string
     */
    protected ?string $name = null;

    /**
     * The platform the webhook is registered for.
     *
     * @var Platform
     */
    protected $platform;

    /**
     * The queue name for the webhook.
     *
     * @var ?string
     */
    protected ?string $queueName = null;

    /**
     * The timeout for the webhook.
     *
     * @var ?int
     */
    protected ?int $timeout = null;

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
     * Get the queue name for the webhook.
     */
    public function queueName(): string
    {
        if ($this->queueName !== null) {
            return $this->queueName;
        }

        return config('platform-resolver.webhooks.default_queue');
    }

    /**
     * Get the timeout for the webhook.
     */
    public function timeout(): int
    {
        if ($this->timeout !== null) {
            return $this->timeout;
        }

        return config('platform-resolver.webhooks.default_timeout');
    }

    /**
     * Set the platform the webhook is registered for.
     */
    public function setPlatform(Platform $platform): void
    {
        $this->platform = $platform;
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
