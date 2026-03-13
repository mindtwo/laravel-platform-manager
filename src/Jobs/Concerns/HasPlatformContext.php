<?php

namespace mindtwo\LaravelPlatformManager\Jobs\Concerns;

use mindtwo\LaravelPlatformManager\Platform;

trait HasPlatformContext
{
    public ?int $platformContextId = null;

    public ?string $platformContextResolver = null;

    /**
     * Capture the current platform context for queue serialization.
     * Call at the end of __construct().
     */
    protected function capturePlatformContext(): void
    {
        $platform = resolve(Platform::class);

        if ($platform->isResolved()) {
            $data = $platform->serializeForQueue();
            $this->platformContextId = $data['platform_id'];
            $this->platformContextResolver = $data['resolver'];
        }
    }

    /**
     * Restore the platform context from DB.
     * Call at the start of handle().
     */
    protected function restorePlatformContext(): void
    {
        if ($this->platformContextId !== null) {
            resolve(Platform::class)->restoreFromId($this->platformContextId);
        }
    }
}
