<?php

namespace mindtwo\LaravelPlatformManager\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use mindtwo\LaravelPlatformManager\Models\Platform as PlatformModel;

/**
 * @property int $platform_id
 * @property-read PlatformModel $platform
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> forCurrentPlatform()
 * @method static \Illuminate\Database\Eloquent\Builder<static> forPlatform(PlatformModel|int $platform)
 */
trait BelongsToPlatform
{
    public static function bootBelongsToPlatform(): void
    {
        static::creating(function (self $model) {
            $key = $model->getPlatformForeignKey();

            if (! isset($model->{$key}) && platform()->isResolved()) {
                $model->{$key} = platform()->get()->getKey();
            }
        });
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(
            config('platform.model', PlatformModel::class),
            $this->getPlatformForeignKey()
        );
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeForCurrentPlatform(Builder $query): Builder
    {
        return $query->where($this->getPlatformForeignKey(), platform()->get()?->getKey());
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeForPlatform(Builder $query, PlatformModel|int $platform): Builder
    {
        $id = $platform instanceof PlatformModel ? $platform->getKey() : $platform;

        return $query->where($this->getPlatformForeignKey(), $id);
    }

    public function getPlatformForeignKey(): string
    {
        return 'platform_id';
    }
}
