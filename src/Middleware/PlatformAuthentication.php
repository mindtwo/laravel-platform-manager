<?php

namespace mindtwo\LaravelPlatformManager\Middleware;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;
use mindtwo\LaravelPlatformManager\Models\AuthToken;
use mindtwo\LaravelPlatformManager\Services\PlatformResolver;

/**
 * Middleware to set the current Platforms main hostname as Session Domain.
 */
class PlatformAuthentication
{
    public function __construct(
        protected PlatformResolver $platformResolver,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|integer $type
     * @return RedirectResponse|Response
     */
    public function handle(Request $request, Closure $next, string|int $type)
    {
        $tokenType = AuthTokenTypeEnum::instance($type);

        $platformToken = $request->header($tokenType->getHeaderName(), null);

        // check if required headers are set
        if ($platformToken === null) {
            response()->json(['message' => 'Unauthenticated. Missing required headers.'], 401)->send();
            die;
        }

        // check if auth token is valid
        if (!$this->platformResolver->checkAuth($tokenType)) {
            response()->json(['message' => 'Unauthenticated. Check provided credentials.'], 401)->send();
            die;
        }

        return $next($request);
    }
}
