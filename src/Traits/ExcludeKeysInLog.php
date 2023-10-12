<?php

namespace mindtwo\LaravelPlatformManager\Traits;

trait ExcludeKeysInLog
{

    /**
     * Exclude fields from logging in database.
     *
     * @var array<string>
     */
    protected array $excludeFromLog = [];

    /**
     * Get the payload to save in database.
     *
     * @param array<string, mixed> $payload
     * @return array
     */
    public function payloadToSave(array $payload): array
    {
        return collect($payload)
            ->replace(array_fill_keys($this->excludeFromLog, '[EXCLUDED]'))
            ->toArray();
    }

}
