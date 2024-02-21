<?php

namespace mindtwo\LaravelPlatformManager\Models\V2;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use mindtwo\LaravelPlatformManager\Builders\WebhookRequestBuilder;

/**
 * @property int $id
 * @property ?int $platform_id
 * @property string $ulid
 * @property string|null $hook
 * @property string|null $requested_from
 * @property string|null $response_url
 * @property mixed $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WebhookRequest extends Model
{
    use SoftDeletes;

    protected $table = 'webhook_requests_v2';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'platform_id',
        'hook',
        'ulid',
        'requested_from',
        'response_url',
        'status',
        'payload',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'json',
    ];

    public function response(): MorphOne
    {
        return $this->morphOne(WebhookResponse::class, 'responseable');
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(config('platform-resolver.model'), 'platform_id');
    }

    public static function query(): WebhookRequestBuilder|Builder
    {
        return parent::query();
    }

    public function newEloquentBuilder($query): WebhookRequestBuilder
    {
        return new WebhookRequestBuilder($query);
    }
}
