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
 * @property string $webhook_url
 * @property Platform $platform
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static query()
 */
class WebhookConfiguration extends Model
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

    public function webhookUrl(): Attribute
    {
        return Attribute::make(fn () => "https://{$this->platform->hostname}{$this->url}");
    }

    /**
     * Platform that received hook call.
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(config('platform-resolver.model'), 'platform_id');
    }
}
