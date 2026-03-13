<?php

namespace mindtwo\LaravelPlatformManager\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use mindtwo\LaravelPlatformManager\LaravelPlatformManagerProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * Get package providers.
     *
     * @param  Application  $app
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            LaravelPlatformManagerProvider::class,
        ];
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
