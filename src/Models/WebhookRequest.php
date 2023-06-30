<?php

namespace mindtwo\LaravelPlatformManager\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use mindtwo\LaravelPlatformManager\Builders\WebhookBuilder;
use mindtwo\LaravelPlatformManager\Enums\WebhookTypeEnum;

/**
 * @property int $id
 * @property string $uuid
 * @property string|null $hook
 * @property WebhookTypeEnum $type
 * @property mixed $request
 * @property mixed $reponse
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WebhookRequest extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'hook',
        'url',
        'status',
        'request',
        'response',
        'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request' => 'json',
        'response' => 'json',
        'type' => WebhookTypeEnum::class,
    ];

    public static function query(): WebhookBuilder|Builder
    {
        return parent::query();
    }

    public function newEloquentBuilder($query): WebhookBuilder
    {
        return new WebhookBuilder($query);
    }
}
