<?php

namespace mindtwo\LaravelPlatformManager\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use mindtwo\LaravelPlatformManager\Casts\AsSettings;
use mindtwo\LaravelPlatformManager\Settings\PlatformSettings;

/**
 * @property int $id
 * @property string $uuid
 * @property bool $is_active
 * @property string|null $hostname
 * @property array<string>|null $additional_hostnames
 * @property string|null $context
 * @property array<string>|null $scopes
 * @property PlatformSettings $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, AuthToken> $authTokens
 * @property-read int|null $auth_tokens_count
 *
 * @method static Builder<static>|Platform byContext(string $context)
 * @method static Builder<static>|Platform byHostname(string $hostname)
 * @method static Builder<static>|Platform byToken(string $token)
 * @method static Builder<static>|Platform isActive()
 * @method static Builder<static>|Platform newModelQuery()
 * @method static Builder<static>|Platform newQuery()
 * @method static Builder<static>|Platform onlyTrashed()
 * @method static Builder<static>|Platform query()
 * @method static Builder<static>|Platform withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Platform withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Platform extends Model
{
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'is_active',
        'hostname',
        'additional_hostnames',
        'context',
        'scopes',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'additional_hostnames' => 'array',
        'scopes' => 'array',
        'settings' => AsSettings::class,
    ];

    /**
     * Read a setting value using dot notation.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings->toArray(), $key, $default);
    }

    /**
     * Platform auth tokens.
     *
     * @return HasMany<AuthToken, $this>
     */
    public function authTokens(): HasMany
    {
        return $this->hasMany(AuthToken::class, 'platform_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** @param Builder<Platform> $query */
    public function scopeIsActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<Platform> $query */
    public function scopeByHostname(Builder $query, string $hostname): void
    {
        $query->where(function ($q) use ($hostname) {
            // Primary hostname: exact or wildcard pattern (e.g. *.app.tld, my.app.*)
            $q->where('hostname', $hostname)
                ->orWhereRaw("? LIKE REPLACE(hostname, '*', '%')", [$hostname]);

            // Additional hostnames: exact or wildcard pattern via json_each
            $q->orWhereRaw(
                "EXISTS (SELECT 1 FROM json_each(additional_hostnames) WHERE ? LIKE REPLACE(value, '*', '%'))",
                [$hostname]
            );
        });
    }

    /** @param Builder<Platform> $query */
    public function scopeByToken(Builder $query, string $token): void
    {
        $query->whereExists(
            fn (QueryBuilder $builder) => $builder->select(DB::raw(1))
                ->from('auth_tokens')
                ->whereColumn('auth_tokens.platform_id', 'platforms.id')
                ->where('auth_tokens.token', $token)
                ->where(fn ($q) => $q->whereNull('auth_tokens.expired_at')
                    ->orWhere('auth_tokens.expired_at', '>', now()))
        );
    }

    /** @param Builder<Platform> $query */
    public function scopeByContext(Builder $query, string $context): void
    {
        $query->where('context', $context);
    }

    // -------------------------------------------------------------------------
    // UUID creation
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
