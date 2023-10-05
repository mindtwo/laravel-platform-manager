<?php

namespace mindtwo\LaravelPlatformManager\Traits;

use mindtwo\LaravelPlatformManager\Models\Platform;

trait OnlyOneMain
{

    /**
     * Boot auto create uuid trait.
     */
    public static function bootOnlyOneMain()
    {
        // Auto populate uuid column on model creation
        static::creating(function ($model) {
            $model->unsetOtherMainPlatform();
        });

        static::saving(function ($model) {
            $model->unsetOtherMainPlatform();
        });
    }

    protected function unsetOtherMainPlatform(): void
    {
        if ($this->is_main && $this->existsOtherMainPlatform()) {
            Platform::query()->isMain()->whereNot('id', $this->id)->update(['is_main' => 0]);
        }
    }

    /**
     * Check if there is another main platform.
     *
     * @return boolean
     */
    protected function existsOtherMainPlatform(): bool
    {
        return Platform::query()->isMain()->whereNot('id', $this->id)->exists();
    }

}
