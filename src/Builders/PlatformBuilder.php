<?php

namespace mindtwo\LaravelPlatformManager\Builders;

use Illuminate\Database\Eloquent\Builder;
use mindtwo\LaravelPlatformManager\Enums\PlatformVisibility;

class PlatformBuilder extends Builder
{
    /**
     * Only platforms with frontend
     *
     * @return PlatformBuilder
     */
    public function isMain(): PlatformBuilder
    {
        return $this->where('is_main', true);
    }

    /**
     * Only visible platforms
     *
     * @return PlatformBuilder
     */
    public function visible(): PlatformBuilder
    {
        return $this->where('visibility', PlatformVisibility::Public());
    }

    /**
     * Filter platforms by their hostname
     *
     * @param string $hostname
     *
     * @return PlatformBuilder
     */
    public function byHostname(string $hostname): PlatformBuilder
    {
        return $this
            ->where('hostname', $hostname)
            ->orWhere(fn (self $query) => $query->whereJsonContains('additional_hostnames', $hostname));
    }
}
