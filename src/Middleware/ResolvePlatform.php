<?php

namespace mindtwo\LaravelPlatformManager\Middleware;

use Closure;
use Illuminate\Http\Request;
use mindtwo\LaravelPlatformManager\Models\Platform as PlatformModel;
use mindtwo\LaravelPlatformManager\Platform;
use mindtwo\LaravelPlatformManager\Repositories\PlatformRepository;

class ResolvePlatform
{
    /**
     * Allowed resolver names.
     *
     * @var array<string>
     */
    private array $allowedResolver = ['host', 'token', 'context', 'session'];

    /** @var array<string> Scopes carried from resolveByToken() to handle(). */
    private array $pendingScopes = [];

    public function __construct(
        protected Platform $platform,
        protected PlatformRepository $repository,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $resolve = 'host'): mixed
    {
        $resolveOrder = array_filter(
            explode('|', $resolve),
            fn ($resolver) => in_array($resolver, $this->allowedResolver)
        );

        $resolvedPlatform = null;
        $matchedResolver = null;

        foreach ($resolveOrder as $resolver) {
            $resolvedPlatform = match ($resolver) {
                'host'    => $this->repository->resolveByHostname($request),
                'token'   => $this->resolveByToken($request),
                'context' => $this->repository->resolveByContext($request),
                'session' => $this->repository->resolveBySession($request),
                default   => null,
            };

            if ($resolvedPlatform !== null) {
                $matchedResolver = $resolver;
                break;
            }
        }

        abort_if($resolvedPlatform === null, 404, 'No platform found.');

        assert($matchedResolver !== null);

        $this->platform->set($resolvedPlatform, $matchedResolver, $this->pendingScopes);

        return $next($request);
    }

    protected function resolveByToken(Request $request): ?PlatformModel
    {
        if (! $result = $this->repository->resolveByToken($request)) {
            return null;
        }

        [$platform, $scopes] = $result;

        $this->pendingScopes = $scopes;

        return $platform;
    }
}
