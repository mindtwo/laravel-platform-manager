<?php

namespace mindtwo\LaravelPlatformManager\Middleware;

use Closure;
use Illuminate\Http\Request;
use mindtwo\LaravelPlatformManager\Platform;

class EnsurePlatformScope
{
    public function __construct(
        protected Platform $platform,
    ) {}

    /**
     * Handle an incoming request.
     *
     * Aborts with 403 if the resolved platform does not have all of the
     * required scopes. Pass scopes as comma-separated middleware parameters:
     *
     *   'platform-scope:read'
     *   'platform-scope:read,write'
     */
    public function handle(Request $request, Closure $next, string ...$scopes): mixed
    {
        foreach ($scopes as $scope) {
            if (! $this->platform->can($scope)) {
                abort(403, "Missing required platform scope: {$scope}.");
            }
        }

        return $next($request);
    }
}
