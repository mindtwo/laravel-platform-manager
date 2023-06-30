<?php

namespace mindtwo\LaravelPlatformManager\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use mindtwo\LaravelPlatformManager\Models\Platform;
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
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $currentPlatform = $this->platformResolver->getCurrentPlatform();

        if (isset($currentPlatform->hostname)) {
            config([
                'session.domain' => $currentPlatform->hostname,
                'session.cookie' => $this->getCookieName($currentPlatform),
            ]);
        }

        return $next($request);
    }

    /**
     * Get the cookie name for the given platform.
     */
    private function getCookieName(Platform $platform): string
    {
        $platformSlug = Str::slug($platform->name ?? '', '_');
        $appSlug = Str::slug(config('app.name'), '_');

        return implode('_', [$platformSlug, $appSlug, 'session']);
    }
}
