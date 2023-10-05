<?php

namespace mindtwo\LaravelPlatformManager\Tests;

use Illuminate\Contracts\Config\Repository;
use mindtwo\LaravelPlatformManager\Providers\LaravelPlatformManagerProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        tap($app->make('config'), function (Repository $config) {
            $config->set('platform-resolver.webhooks.enabled', true);
            $config->set('platform-resolver.webhooks.endpoint', '/v1/webhooks');

            $config->set('webhooks.example', [

                /**
                 * Validation rules for received data
                 */
                'rules' => [
                    'foo' => 'required|string',
                ],

                'responseCallback' => null,
            ]);

        });
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            LaravelPlatformManagerProvider::class,
        ];
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/upgrade-hooks');
    }
}
