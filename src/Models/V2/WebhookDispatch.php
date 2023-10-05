<?php

namespace mindtwo\LaravelPlatformManager\Models\V2;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use mindtwo\LaravelPlatformManager\Builders\WebhookResponseBuilder;
use mindtwo\LaravelPlatformManager\Enums\DispatchStatusEnum;

/**
 * @property int $id
 * @property int $platform_id
 * @property string $ulid
 * @property string $url
 * @property string $hook
 * @property string $dispatch_class
 * @property DispatchStatusEnum $status
 * @property mixed $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WebhookDispatch extends Model
{
    use SoftDeletes;

    protected $table = 'webhook_dispatches_v2';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'hook',
        'ulid',
        'url',
        'dispatch_class',
        'status',
        'payload',
        'platform_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'json',
        'status' => DispatchStatusEnum::class,
    ];

    public function response(): MorphOne
    {
        return $this->morphOne(WebhookResponse::class, 'responseable');
    }

    public static function query(): WebhookResponseBuilder|Builder
    {
        return parent::query();
    }

    public function newEloquentBuilder($query): WebhookResponseBuilder
    {
        return new WebhookResponseBuilder($query);
    }
}
