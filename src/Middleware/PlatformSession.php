<?php

namespace mindtwo\LaravelPlatformManager\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use mindtwo\LaravelPlatformManager\Services\PlatformResolver;

/**
 * Middleware to set the current Platforms main hostname as Session Domain.
 */
class PlatformSession
{
    public function __construct(
        protected PlatformResolver $platformResolver,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $currentPlatform = $this->platformResolver->getCurrentPlatform();

        if (! empty($currentPlatform) && isset($currentPlatform->hostname)) {
            config([
                'session.domain' => $currentPlatform->hostname,
                'session.cookie' => $this->getCookieName($currentPlatform),
            ]);
        }

        return $next($request);
    }

    private function getCookieName($platform)
    {
        $platformSlug = Str::slug($platform->name, '_');
        $appSlug = Str::slug(config('app.name'), '_');

        return join('_', [$platformSlug, $appSlug, 'session']);
    }
}
