<?php

namespace mindtwo\LaravelPlatformManager\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use mindtwo\LaravelPlatformManager\Settings\PlatformSettings;

/**
 * @implements CastsAttributes<PlatformSettings, PlatformSettings|array<string, mixed>>
 */
class AsSettings implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): PlatformSettings
    {
        /** @var class-string<PlatformSettings> $class */
        $class = config('platform.settings', PlatformSettings::class);

        $data = is_string($value) ? json_decode($value, true) : $value;

        return $class::fromArray($data ?? []);
    }

    /**
     * @param  PlatformSettings|array<string, mixed>|null  $value
     * @return array<string, string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        /** @var class-string<PlatformSettings> $class */
        $class = config('platform.settings', PlatformSettings::class);

        if (is_array($value)) {
            $value = $class::fromArray($value);
        }

        $data = $value instanceof PlatformSettings ? $value->toStorageArray() : [];

        return [$key => json_encode($data) ?: null];
    }
}
