<?php

namespace mindtwo\LaravelPlatformManager\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use mindtwo\LaravelPlatformManager\Models\Platform;

/**
 * Middleware to set the current Platforms main hostname as Session Domain.
 */
class PlatformSession
{
    public function __construct(
        private Platform $currentPlatform
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
        if (! $this->currentPlatform->is_headless && isset($this->currentPlatform->hostname)) {
            config([
                'session.domain' => $this->currentPlatform->hostname,
                'session.cookie' => $this->getCookieName($this->currentPlatform),
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
