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
        $this->publishConfig();
        $this->publishMigration();

        $this->loadRoutesFrom(__DIR__.'/../../routes/webhooks.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/platform-resolver.php', 'platform-resolver');

        $this->app->scoped(PlatformResolver::class, function ($app) {
            $request = $app->make(Request::class);

            return new PlatformResolver($request);
        });
    }

    /**
     * Publish the config file.
     *
     * @return void
     */
    protected function publishConfig()
    {
        $configPath = __DIR__.'/../../config/platform-resolver.php';
        $hookPath = __DIR__.'/../../config/webhooks.php';

        $this->publishes([
            $configPath => config_path('platform-resolver.php'),
            $hookPath => config_path('webhooks.php'),
        ], ['config', 'platform-resolver']);
    }

    /**
     * Publish migration.
     *
     * @return void
     */
    protected function publishMigration()
    {
        if (class_exists('CreatePlatformsTable')) {
            return;
        }

        $this->publishes([
            __DIR__.'/../../database/migrations/create_platforms_table.php' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_platforms_table.php'),
            __DIR__.'/../../database/migrations/create_auth_tokens_table.php' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_auth_tokens_table.php'),
            __DIR__.'/../../database/migrations/create_webhooks_table.php' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_webhooks_table.php'),
        ], ['migrations', 'platform-resolver']);
    }
}
