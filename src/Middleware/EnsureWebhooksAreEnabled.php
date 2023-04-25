<?php

namespace mindtwo\LaravelPlatformManager\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to set the current Platforms main hostname as Session Domain.
 */
class EnsureWebhooksAreEnabled
{
    public function __construct(
        // protected PlatformResolver $platformResolver,
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
        // TODO activate/deactivate for platforms
        if (! config('platform-resolver.webhooks.enabled', false)) {
            return response('Not Found', 404);
        }

        return $next($request);
    }
}
