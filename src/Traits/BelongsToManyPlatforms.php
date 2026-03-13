<?php

namespace mindtwo\LaravelPlatformManager\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use mindtwo\LaravelPlatformManager\Models\Platform as PlatformModel;

/**
 * @property-read Collection<int, PlatformModel> $platforms
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> forCurrentPlatform()
 * @method static \Illuminate\Database\Eloquent\Builder<static> forPlatform(PlatformModel|int $platform)
 */
trait BelongsToManyPlatforms
{
    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(
            config('platform.model', PlatformModel::class),
            $this->getPlatformPivotTable(),
        );
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeForCurrentPlatform(Builder $query): Builder
    {
        return $query->whereHas('platforms', function (Builder $q) {
            $q->where('platforms.id', platform()->get()?->getKey());
        });
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeForPlatform(Builder $query, PlatformModel|int $platform): Builder
    {
        $id = $platform instanceof PlatformModel ? $platform->getKey() : $platform;

        return $query->whereHas('platforms', fn (Builder $q) => $q->where('platforms.id', $id));
    }

    public function getPlatformPivotTable(): string
    {
        return 'platform_'.str(class_basename($this))->snake()->plural();
    }
}
