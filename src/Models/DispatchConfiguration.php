<?php

namespace mindtwo\LaravelPlatformManager\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use mindtwo\LaravelAutoCreateUuid\AutoCreateUuid;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $platform_id
 * @property string $hook
 * @property string $auth_token
 * @property string $url
 * @property string $endpoint
 * @property ?Platform $platform
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static query()
 */
class DispatchConfiguration extends Model
{
    use AutoCreateUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'hook',
        'active',
        'url',
        'auth_token',
        'platform_id',
    ];

    public function endpoint(): Attribute
    {
        return Attribute::make(function () {
            if (str_starts_with($this->url, 'https://')) {
                return $this->url;
            }

            if (! is_null($this->platform)) {
                return "https://{$this->platform->hostname}{$this->url}";
            }

            throw new \Exception("Invalid configuration exception. The configuration for {$this->hook} has no valid endpoint configured.", 1);
        });
    }

    /**
     * Platform that received hook call.
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(config('platform-resolver.model'), 'platform_id');
    }
}
