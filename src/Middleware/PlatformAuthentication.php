<?php

namespace mindtwo\LaravelPlatformManager\Middleware;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $type = 'public')
    {
        if (!in_array($type, ['public', 'secret'])) {
            throw new \Exception("Token type '$type' is invalid. Valid types are 'public' or 'secret'", 1);
        }

        $tokenHeaderName = $type === 'secret' ? 'X-Context-Platform-Auth-Token' : 'X-Context-Platform-Public-Auth-Token';

        $platformToken = $request->header($tokenHeaderName, null);
        $platformHost = $request->header('X-Platform-Host', null);

        // check if required headers are set
        if ($platformToken === null || $platformHost === null) {
            response()->json(['message' => 'Unauthenticated. Missing required headers.'], 401)->send();
            die;
        }

        $tokenTypeValue = $type === 'secret' ? AuthTokenTypeEnum::Secret() : AuthTokenTypeEnum::Public();
        // check if auth token is valid
        $tokenValid = AuthToken::query()
            ->where([
                'token' => $platformToken,
                'type' => $tokenTypeValue,
            ])
            ->whereHas('platform', function (Builder $query) use ($platformHost) {
                $query->where([
                    'hostname' => $platformHost,
                    'visibility' => true,
                ]);
            })
            ->exists();

        if (!$tokenValid) {
            response()->json(['message' => 'Unauthenticated. Check provided credentials.'], 401)->send();
            die;
        }

        return $next($request);
    }
}
