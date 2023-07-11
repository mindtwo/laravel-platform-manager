<?php

namespace mindtwo\LaravelPlatformManager\Services;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;
use mindtwo\LaravelPlatformManager\Models\AuthToken;
use mindtwo\LaravelPlatformManager\Models\Platform;

class PlatformResolver
{
    /**
     * Current platform for host.
     */
    private ?Platform $current = null;

    public function __construct(
        protected Request $request,
    ) {
    }

    /**
     * Check if request is Authenticated via platform auth.
     * Platform must only be visible. When mode is strict
     * the hostname is also checked.
     */
    public function checkAuth(AuthTokenTypeEnum $tokenType, ?Request $request = null, bool $strict = false): bool
    {
        $request = $request ?? $this->request;

        if (null !== ($token = $request->header($tokenType->getHeaderName()))) {
            return AuthToken::query()
                ->where([
                    'token' => $token,
                    'type' => $tokenType(),
                ])
                ->whereHas('platform', function (Builder $query) use ($strict) {
                    $query
                        ->when(
                            $strict,
                            fn ($query) => $query->where('hostname', $this->request->getHost())
                        )
                        ->where('visibility', true);
                })
                ->exists();
        }

        return false;
    }

    /**
     * Get current Platform. Return type is your configured
     * eloquent platform model class. See: config('platform-resolver.model')
     *
     * @return mixed
     */
    public function getCurrentPlatform()
    {
        if (isset($this->current)) {
            return $this->current;
        }

        /** @var Platform $model */
        $model = app(config('platform-resolver.model'));

        if (($headerName = AuthTokenTypeEnum::Public->getHeaderName()) && $this->request->hasHeader($headerName)) {
            $this->current = $model->query()->byPublicAuthToken($this->request->header('X-Context-Platform-Public-Auth-Token'))->first();
        }

        if (($headerName = AuthTokenTypeEnum::Secret->getHeaderName()) && $this->request->hasHeader($headerName)) {
            $this->current = $model->query()->bySecretAuthToken($this->request->header($headerName))->first();
        }

        // Check for hostname
        if (empty($this->current)) {
            $this->current = $model->query()->byHostname($this->request->getHost())->first();
        }

        // Fallback primary platform
        if (empty($this->current)) {
            $this->current = $model->query()->isMain()->firstOrFail();
        }

        return $this->current;
    }
}
