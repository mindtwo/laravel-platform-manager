<?php

namespace mindtwo\LaravelPlatformManager\Testing;

use mindtwo\LaravelPlatformManager\Models\Platform as PlatformModel;
use mindtwo\LaravelPlatformManager\Platform;

class PlatformFake
{
    /**
     * Set an unsaved platform model on the singleton — no database required.
     *
     * @param array<string, mixed> $attributes
     * @param array<string>        $scopes
     */
    public static function make(
        array $attributes = [],
        string $resolver = 'fake',
        array $scopes = [],
    ): Platform {
        /** @var class-string<PlatformModel> $modelClass */
        $modelClass = config('platform.model', PlatformModel::class);

        /** @var PlatformModel $model */
        $model = (new $modelClass)->forceFill(array_merge([
            'id'        => 1,
            'uuid'      => (string) \Illuminate\Support\Str::uuid(),
            'is_active' => true,
        ], $attributes));

        $platform = app(Platform::class);
        $platform->set($model, $resolver, $scopes);

        return $platform;
    }

    /**
     * Reset the platform singleton to an unresolved state.
     */
    public static function reset(): void
    {
        app()->forgetInstance(Platform::class);
        app()->singleton(Platform::class, fn () => new Platform);
    }
}
