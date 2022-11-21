<?php

namespace mindtwo\LaravelPlatformManager\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use mindtwo\LaravelPlatformManager\Services\PlatformResolver;

class LaravelPlatformManagerProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../../config/platform-resolver.php';
        $this->publishes([$configPath => $this->getConfigPath()], 'config');
        $this->loadMigrationsFrom($this->getMigrationsPath());
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../../config/platform-resolver.php';
        $this->mergeConfigFrom($configPath, 'platform-resolver');

        $this->app->scoped(PlatformResolver::class, function ($app) {
            $request = $app->make(Request::class);

            return new PlatformResolver($request);
        });
    }

    /**
     * Get our migration paths
     *
     * @return array
     */
    private function getMigrationsPath(): array
    {
        return is_array(config('platforms.migrations_path')) ?
            config('platforms.migrations_path') : [__DIR__.'/../../database/migrations', __DIR__.'/../../database/platforms'];
    }

    /**
     * Get the config path
     *
     * @return string
     */
    protected function getConfigPath()
    {
        return config_path('platform-resolver.php');
    }

    /**
     * Publish the config file
     *
     * @param  string $configPath
     */
    protected function publishConfig($configPath)
    {
        $this->publishes([$configPath => config_path('platform-resolver.php')], 'config');
    }
}
