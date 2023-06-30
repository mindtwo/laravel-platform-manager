<?php

namespace mindtwo\LaravelPlatformManager\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use mindtwo\LaravelAutoCreateUuid\AutoCreateUuid;
use mindtwo\LaravelPlatformManager\Builders\PlatformBuilder;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $owner_id
 * @property bool|null $is_main
 * @property bool|null $visibility
 * @property string|null $name
 * @property string|null $hostname
 * @property array|null $additional_hostnames
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static PlatformBuilder query()
 */
class Platform extends Model
{
    use SoftDeletes;
    use AutoCreateUuid;

    // TODO implement contract and move to config?
    protected static string $authTokenModel = AuthToken::class;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_main' => 'boolean',
        'visibility' => 'boolean',
        'additional_hostnames' => 'array',
    ];

    /**
     * Get url to logo file.
     *
     * @deprecated version 2.0.0
     */
    public function getLogoUrlAttribute(): string
    {
        return '';
    }

    /**
     * Platform webhooks.
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class, 'platform_id');
    }

    /**
     * Platform webhooks.
     */
    public function webhookConfigurations(): HasMany
    {
        return $this->hasMany(WebhookConfiguration::class, 'platform_id');
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public function newEloquentBuilder($query): PlatformBuilder
    {
        return new PlatformBuilder($query);
    }

    /**
     * Begin querying the model.
     */
    public static function query(): PlatformBuilder|Builder
    {
        return parent::query();
    }
}
