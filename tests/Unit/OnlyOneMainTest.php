<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use mindtwo\LaravelPlatformManager\Tests\Fake\SingleMainPlatform;

uses(RefreshDatabase::class);

function createSingleMainPlatform()
{
    $platform = new SingleMainPlatform;

    $platform->is_main = true;
    $platform->name = 'Test Platform';
    $platform->save();

    return $platform;
}

it('can only exists one main platform if the trait is used', function () {
    createSingleMainPlatform();
    expect(SingleMainPlatform::query()->isMain()->count())->toBe(1);

    createSingleMainPlatform();
    expect(SingleMainPlatform::query()->isMain()->count())->toBe(1);

    $platforms = SingleMainPlatform::query()->get();

    expect($platforms->first()->is_main)->toBeFalse()
        ->and($platforms->last()->is_main)->toBeTrue();

    // test if the trait is working on update only if we set is_main to true
    $platforms->first()->is_main = false;
    $platforms->first()->save();

    // when we set is_main to false, we dont want to update
    $platforms = SingleMainPlatform::query()->get();
    expect($platforms->first()->is_main)->toBeFalse()
        ->and($platforms->last()->is_main)->toBeTrue()
        ->and(SingleMainPlatform::query()->isMain()->count())->toBe(1);

    $platforms->first()->is_main = true;
    $platforms->first()->save();

    $platforms = SingleMainPlatform::query()->get();

    expect(SingleMainPlatform::query()->isMain()->count())->toBe(1)
        ->and($platforms->first()->is_main)->toBeTrue()
        ->and($platforms->last()->is_main)->toBeFalse();
});
