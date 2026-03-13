<?php

namespace mindtwo\LaravelPlatformManager\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $platform_id
 * @property Platform $platform
 * @property array<string> $scopes
 * @property string $token
 * @property Carbon|null $expired_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder<static>|AuthToken newModelQuery()
 * @method static Builder<static>|AuthToken newQuery()
 * @method static Builder<static>|AuthToken notExpired()
 * @method static Builder<static>|AuthToken query()
 * @method static Builder<static>|AuthToken withScope(string $scope)
 *
 * @mixin \Eloquent
 */
class AuthToken extends Model
{
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scopes' => 'array',
        'expired_at' => 'datetime',
    ];

    protected $fillable = [
        'platform_id',
        'scopes',
        'token',
        'expired_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->token)) {
                $model->token = Hash::make(Str::random(75));
            }
        });
    }

    public function isExpired(): bool
    {
        return $this->expired_at !== null && $this->expired_at->isPast();
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? []);
    }

    /**
     * @param  Builder<AuthToken>  $query
     */
    public function scopeNotExpired(Builder $query): void
    {
        $query->where(function ($q) {
            $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
        });
    }

    /**
     * @param  Builder<AuthToken>  $query
     * @return Builder<AuthToken>
     */
    public function scopeWithScope(Builder $query, string $scope): Builder
    {
        return $query->whereJsonContains('scopes', $scope);
    }

    /**
     * Platform.
     *
     * @return BelongsTo<Platform, $this>
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }
}
