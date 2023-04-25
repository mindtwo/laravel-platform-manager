<?php

namespace mindtwo\LaravelPlatformManager\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use mindtwo\LaravelAutoCreateUuid\AutoCreateUuid;
use mindtwo\LaravelPlatformManager\Builders\PlatformBuilder;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $platform_id
 * @property bool|null $processed
 * @property string|null $hook
 * @property mixed|null $data
 * @property Carbon|null $processed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static query()
 */
class Webhook extends Model
{
    use SoftDeletes;
    use AutoCreateUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'hook',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'json',
    ];

    /**
     * Check if Webhook is processed.
     */
    public function processed(): Attribute
    {
        return Attribute::make(fn () => $this->processed_at !== null);
    }

    /**
     * Platform that received hook call.
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(config('platform-resolver.model'), 'platform_id');
    }

    public function newEloquentBuilder($query): PlatformBuilder
    {
        return new PlatformBuilder($query);
    }

    public function getLogoUrlAttribute(): string
    {
        return $this->logo_file ? asset('storage/'.$this->logo_file) : '';
    }
}
