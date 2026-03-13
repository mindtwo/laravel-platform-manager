<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use mindtwo\LaravelPlatformManager\Platform;
use mindtwo\LaravelPlatformManager\Tests\Fake\ConfigurableSettings;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;

uses(RefreshDatabase::class);

describe('ConfigOverride', function () {
    it('applies config overrides defined in $configKeys when platform is set', function () {
        config(['platform.settings' => ConfigurableSettings::class]);

        $model = (new PlatformFactory())->create([
            'settings' => ['timezone' => 'America/New_York'],
        ]);

        $platform = app(Platform::class);
        $platform->set($model, 'test');

        expect(config('app.timezone'))->toBe('America/New_York');
    });

    it('does not override config when settings are absent', function () {
        $original = config('app.timezone');

        $model = (new PlatformFactory())->create(['settings' => null]);

        $platform = app(Platform::class);
        $platform->set($model, 'test');

        expect(config('app.timezone'))->toBe($original);
    });

    it('does not override config when $configKeys is empty', function () {
        $original = config('app.timezone');

        $model = (new PlatformFactory())->create([
            'settings' => ['timezone' => 'America/New_York'],
        ]);

        $platform = app(Platform::class);
        $platform->set($model, 'test');

        expect(config('app.timezone'))->toBe($original);
    });

    it('does not inject null values', function () {
        config(['platform.settings' => ConfigurableSettings::class]);

        $original = config('app.timezone');

        $model = (new PlatformFactory())->create(['settings' => []]);

        $platform = app(Platform::class);
        $platform->set($model, 'test');

        expect(config('app.timezone'))->toBe($original);
    });
});
