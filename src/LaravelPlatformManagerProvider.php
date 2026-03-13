<?php

namespace mindtwo\LaravelPlatformManager;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use mindtwo\LaravelPlatformManager\Middleware\EnsurePlatformScope;
use mindtwo\LaravelPlatformManager\Middleware\ResolvePlatform;

class LaravelPlatformManagerProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/platform.php', 'platform');

        $this->app->singleton(Platform::class, fn () => new Platform);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/platform.php' => config_path('platform.php'),
        ], ['config', 'platform-manager']);

        $this->publishes([
            __DIR__.'/../database/migrations/create_platforms_table.php'   => database_path('migrations/'.date('Y_m_d_His', time()).'_create_platforms_table.php'),
            __DIR__.'/../database/migrations/create_auth_tokens_table.php' => database_path('migrations/'.date('Y_m_d_His', time() + 1).'_create_auth_tokens_table.php'),
        ], ['migrations', 'platform-manager']);

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('resolve-platform', ResolvePlatform::class);
        $router->aliasMiddleware('platform-scope', EnsurePlatformScope::class);
    }
}
