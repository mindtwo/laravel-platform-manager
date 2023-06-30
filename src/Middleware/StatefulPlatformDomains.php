<?php

namespace mindtwo\LaravelPlatformManager\Middleware;

use Closure;
use Illuminate\Http\Request;
use mindtwo\LaravelPlatformManager\Models\Platform;

/**
 * Middleware to add the current Platforms avaiable hostnames to sanctums stateful domains.
 */
class StatefulPlatformDomains
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
        $stateful = config('sanctum.stateful', []);

        if (! $this->currentPlatform->is_headless && isset($this->currentPlatform->hostname)) {
            $statefulDomains = collect([
                $this->currentPlatform->hostname,
                ...$stateful,
                ...$this->currentPlatform->additional_hostnames ?? [],
            ])
                ->map(fn ($item) => trim($item))
                ->unique()
                ->toArray();

            config(['sanctum.stateful' => $statefulDomains]);
        }

        return $next($request);
    }
}
