<?php

namespace mindtwo\LaravelPlatformManager\Tests\Fake;

use mindtwo\LaravelPlatformManager\Settings\PlatformSettings;

class ConfigurableSettings extends PlatformSettings
{
    public ?string $timezone = null;

    protected array $configKeys = [
        'timezone' => 'app.timezone',
    ];
}
