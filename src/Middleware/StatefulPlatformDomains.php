<?php

namespace mindtwo\LaravelPlatformManager\Middleware;

use Closure;
use Illuminate\Http\Request;
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
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $stateful = config('sanctum.stateful', []);
        $currentPlatform = $this->platformResolver->getCurrentPlatform();

        if (isset($currentPlatform->hostname)) {
            $statefulDomains = collect([
                $currentPlatform->hostname,
                ...$stateful,
                ...$currentPlatform->additional_hostnames ?? [],
            ])
                ->map(fn ($item) => trim($item))
                ->unique()
                ->toArray();

            config(['sanctum.stateful' => $statefulDomains]);
        }

        return $next($request);
    }
}
