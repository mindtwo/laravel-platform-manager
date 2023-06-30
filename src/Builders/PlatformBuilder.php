<?php

namespace mindtwo\LaravelPlatformManager\Builders;

use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;
use mindtwo\LaravelPlatformManager\Models\Platform;

/**
 * @method ?Platform first()
 * @method Platform firstOrFail()
 */
class PlatformBuilder extends Builder
{
    /**
     * Only platforms with frontend
     */
    public function isMain(): self
    {
        return $this->where('is_main', true);
    }

    /**
     * Only visible platforms
     */
    public function visible(): self
    {
        return $this->where('visibility', true);
    }

    /**
     * Filter platforms by their hostname
     */
    public function byHostname(string $hostname): self
    {
        return $this->where(function ($query) use ($hostname) {
            return $query->where('hostname', $hostname)
                ->orWhere(fn (self $q) => $q->where('additional_hostnames', 'LIKE', "%\"$hostname\"%"));
        });
    }

    public function byPublicAuthToken(string $token): self
    {
        return $this->whereExists(
            fn (QueryBuilder $builder) => $builder->select(DB::raw(1))->from('auth_tokens')
                ->whereColumn('auth_tokens.platform_id', 'platforms.id')
                ->where('auth_tokens.token', $token)
                ->where('auth_tokens.type', AuthTokenTypeEnum::Public())
        );
    }

    public function bySecretAuthToken(string $token): self
    {
        return $this->whereExists(
            fn (QueryBuilder $builder) => $builder->select(DB::raw(1))->from('auth_tokens')
                ->whereColumn('auth_tokens.platform_id', 'platforms.id')
                ->where('auth_tokens.token', $token)
                ->where('auth_tokens.type', AuthTokenTypeEnum::Secret())
        );
    }
}
