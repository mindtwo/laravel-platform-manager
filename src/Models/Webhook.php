<?php

namespace mindtwo\LaravelPlatformManager\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use mindtwo\LaravelAutoCreateUuid\AutoCreateUuid;
use mindtwo\LaravelPlatformManager\Builders\WebhookBuilder;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $platform_id
 * @property string|null $hook
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static query()
 */
class Webhook extends Model
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
        'platform_id',
    ];

    protected $casts = [
        'active' => 'bool',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // prevent creation for hooks that are not predefined
        static::saved(function (Webhook $webhook) {
            $availableHooks = array_keys(config('webhooks'));

            if (! in_array($webhook->hook, $availableHooks)) {
                $webhook->delete();
            }
        });
    }

    /**
     * Platform that received hook call.
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(config('platform-resolver.model'), 'platform_id');
    }

    public function newEloquentBuilder($query): WebhookBuilder
    {
        return new WebhookBuilder($query);
    }
}
