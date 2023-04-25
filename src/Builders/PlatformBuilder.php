<?php

namespace mindtwo\LaravelPlatformManager\Builders;

use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;

class PlatformBuilder extends Builder
{
    /**
     * Only platforms with frontend
     */
    public function isMain(): PlatformBuilder
    {
        return $this->where('is_main', true);
    }

    /**
     * Only visible platforms
     */
    public function visible(): PlatformBuilder
    {
        return $this->where('visibility', true);
    }

    /**
     * Filter platforms by their hostname
     */
    public function byHostname(string $hostname): PlatformBuilder
    {
        return $this
            ->where('hostname', $hostname)
            ->orWhere(fn (self $query) => $query->where('additional_hostnames', 'LIKE', "%\"$hostname\"%"));
    }

    public function byPublicAuthToken(string $token): self|null
    {
        return $this->whereExists(
            fn (QueryBuilder $builder) => $builder->select(DB::raw(1))->from('auth_tokens')
                ->whereColumn('auth_tokens.platform_id', 'platforms.id')
                ->where('auth_tokens.token', $token)
                ->where('auth_tokens.type', AuthTokenTypeEnum::Public())
        );
    }

    public function bySecretAuthToken(string $token): self|null
    {
        return $this->whereExists(
            fn (QueryBuilder $builder) => $builder->select(DB::raw(1))->from('auth_tokens')
                ->whereColumn('auth_tokens.platform_id', 'platforms.id')
                ->where('auth_tokens.token', $token)
                ->where('auth_tokens.type', AuthTokenTypeEnum::Secret())
        );
    }
}
