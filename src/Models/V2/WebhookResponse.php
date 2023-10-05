<?php

namespace mindtwo\LaravelPlatformManager\Models\V2;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use mindtwo\LaravelPlatformManager\Builders\WebhookResponseBuilder;

/**
 * @property int $id
 * @property string $ulid
 * @property string $hook
 * @property mixed $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WebhookResponse extends Model
{
    use SoftDeletes;

    protected $table = 'webhook_responses_v2';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'hook',
        'ulid',
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

    public function responseable(): MorphTo
    {
        return $this->morphTo();
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
