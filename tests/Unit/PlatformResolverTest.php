<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;
use mindtwo\LaravelPlatformManager\Models\AuthToken;
use mindtwo\LaravelPlatformManager\Models\Platform;
use mindtwo\LaravelPlatformManager\Services\PlatformResolver;
use mindtwo\LaravelPlatformManager\Tests\Fake\PlatformFactory;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->make(PlatformResolver::class)->setCurrentPlatform(null);
});

it('can\'t resolve a platform if none are specified', function () {
    $platformResolver = app()->make(PlatformResolver::class);

    expect(Platform::query()->count())->toBe(0);
    expect(fn () => $platformResolver->getCurrentPlatform())->toThrow('No query results for model [mindtwo\LaravelPlatformManager\Models\Platform]');
});

it('can\'t resolve a platform if no main platforms are specified', function () {
    $platformResolver = app()->make(PlatformResolver::class);

    (new PlatformFactory())->count(5)->create();

    expect(Platform::query()->count())->toBe(5);
    expect(fn () => $platformResolver->getCurrentPlatform())->toThrow('No query results for model [mindtwo\LaravelPlatformManager\Models\Platform]');
});

it('can\'t resolve a platform if all are unactive specified', function () {
    $platformResolver = app()->make(PlatformResolver::class);

    (new PlatformFactory())->inactive()->count(5)->create([
        'hostname' => 'localhost',
    ]);

    expect(Platform::query()->count())->toBe(5);
    expect(fn () => $platformResolver->getCurrentPlatform())->toThrow('No query results for model [mindtwo\LaravelPlatformManager\Models\Platform]');
});

it('can resolve a platform if a main platform is specified', function () {
    $platformResolver = app()->make(PlatformResolver::class);

    (new PlatformFactory())->main()->create();

    $this->assertInstanceOf(Platform::class, $platformResolver->getCurrentPlatform());
});

it('can resolve the platform with the specified hostname', function () {
    $platformResolver = app()->make(PlatformResolver::class);

    (new PlatformFactory())->count(2)->create();
    $platform = (new PlatformFactory())->create([
        'hostname' => 'localhost',
    ]);

    (new PlatformFactory())->main()->create();

    expect(Platform::query()->count())->toBe(4);
    expect($platformResolver->getCurrentPlatform()->id)->toBe($platform->id);
});

it('can resolve the platform with secret auth token', function () {
    $platformResolver = app()->make(PlatformResolver::class);

    $platform = (new PlatformFactory())->create();

    $token = new AuthToken();

    $token->type = AuthTokenTypeEnum::Secret();
    $token->platform_id = $platform->id;
    $token->save();

    expect(Platform::query()->count())->toBe(1);

    request()->headers->set(AuthTokenTypeEnum::Secret->getHeaderName(), $token->token);

    expect($platformResolver->getCurrentPlatform()->id)->toBe($platform->id);
});

it('can resolve the platform with public auth token', function () {
    $platformResolver = app()->make(PlatformResolver::class);

    $platform = (new PlatformFactory())->create();

    $token = new AuthToken();

    $token->type = AuthTokenTypeEnum::Public();
    $token->platform_id = $platform->id;
    $token->save();

    expect(Platform::query()->count())->toBe(1);

    request()->headers->set(AuthTokenTypeEnum::Public->getHeaderName(), $token->token);

    expect($platformResolver->getCurrentPlatform()->id)->toBe($platform->id);
});
