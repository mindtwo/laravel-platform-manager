<?php

namespace mindtwo\LaravelPlatformManager\Testing;

use mindtwo\LaravelPlatformManager\Platform;
use PHPUnit\Framework\Assert;

trait InteractsWithPlatform
{
    /**
     * Set a fake platform on the singleton and return it.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string>  $scopes
     */
    public function setPlatform(
        array $attributes = [],
        string $resolver = 'fake',
        array $scopes = [],
    ): Platform {
        return PlatformFake::make($attributes, $resolver, $scopes);
    }

    /**
     * Reset the platform singleton to an unresolved state.
     */
    public function clearPlatform(): void
    {
        PlatformFake::reset();
    }

    public function assertPlatformResolved(): void
    {
        Assert::assertTrue(app(Platform::class)->isResolved(), 'Failed asserting that a platform is resolved.');
    }

    public function assertPlatformNotResolved(): void
    {
        Assert::assertFalse(app(Platform::class)->isResolved(), 'Failed asserting that no platform is resolved.');
    }

    public function assertPlatformCan(string $scope): void
    {
        Assert::assertTrue(
            app(Platform::class)->can($scope),
            "Failed asserting that the platform has scope [{$scope}].",
        );
    }

    public function assertPlatformCannot(string $scope): void
    {
        Assert::assertFalse(
            app(Platform::class)->can($scope),
            "Failed asserting that the platform does not have scope [{$scope}].",
        );
    }

    public function assertPlatformResolver(string $resolver): void
    {
        Assert::assertSame(
            $resolver,
            app(Platform::class)->resolver(),
            "Failed asserting that the platform resolver is [{$resolver}].",
        );
    }
}
