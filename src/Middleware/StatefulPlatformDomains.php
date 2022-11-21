<?php

namespace mindtwo\LaravelPlatformManager\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use mindtwo\LaravelPlatformManager\Services\PlatformResolver;

/**
 * Middleware to add the current Platforms avaiable hostnames to sanctums stateful domains.
 */
class StatefulPlatformDomains
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
        $stateful = config('sanctum.stateful');
        $currentPlatform = $this->platformResolver->getCurrentPlatform();

        if (!empty($currentPlatform) && isset($currentPlatform->hostname)) {
            // trim all possible hostnames
            $stateful = collect(array_merge($stateful, explode(',', $currentPlatform->hostname)))
                ->map(function ($value) {
                    return trim($value);
                })
                ->unique()
                ->toArray();

            config(['sanctum.stateful' => $stateful]);
        }

        return $next($request);
    }
}
