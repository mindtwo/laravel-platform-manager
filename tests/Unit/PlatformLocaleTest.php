<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use mindtwo\LaravelPlatformManager\Models\Platform;
use mindtwo\LaravelPlatformManager\Services\PlatformResolver;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;
use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertEquals;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->make(PlatformResolver::class)->setCurrentPlatform(null);
});


it('it uses the default locale', function () {
    $platform = (new PlatformFactory())->main()->create();

    assertEquals(config('platform-resolver.default_locale'), $platform->default_locale);
    assertContains(config('platform-resolver.default_locale'), $platform->available_locales);
});

it('it uses the platform locale', function () {
    $platform = (new PlatformFactory())->main()->create([
        'default_locale' => 'de',
        'available_locales' => ['de'],
    ]);

    assertEquals('de', $platform->default_locale);
    assertContains('de', $platform->available_locales);
});
