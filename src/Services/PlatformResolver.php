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

    /**
     * PlatformResolver constructor.
     *
     * @param  class-string<Platform>  $platformModel
     */
    public function __construct(
        protected Request $request,
        protected string $platformModel,
    ) {
    }

    /**
     * Check if request is Authenticated via platform auth.
     * Platform must only be visible. When mode is strict
     * the hostname is also checked.
     */
    public function checkAuth(AuthTokenTypeEnum $tokenType, bool $strict = false): bool
    {
        if (null !== ($token = $this->request->header($tokenType->getHeaderName()))) {
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
     */
    public function getCurrentPlatform(): Platform
    {
        if (isset($this->current)) {
            return $this->current;
        }

        if (($headerName = AuthTokenTypeEnum::Public->getHeaderName()) && is_string($token = $this->request->header($headerName))) {
            $this->current = $this->platformModel::query()->visible()->byPublicAuthToken($token)->first();
        }

        if (($headerName = AuthTokenTypeEnum::Secret->getHeaderName()) && is_string($token = $this->request->header($headerName))) {

            $this->current = $this->platformModel::query()->visible()->bySecretAuthToken($token)->first();
        }

        // Check for hostname
        if (empty($this->current)) {
            $this->current = $this->platformModel::query()->visible()->byHostname($this->request->getHost())->first();
        }

        // Fallback primary platform
        if (empty($this->current)) {
            try {
                $this->current = $this->platformModel::query()->visible()->isMain()->firstOrFail();
            } catch (\Throwable $th) {
                // TODO custom exception

                throw $th;
            }
        }

        return $this->current;
    }

    /**
     * Set current platform.
     */
    public function setCurrentPlatform(?Platform $platform): self
    {
        $this->current = $platform;

        return $this;
    }
}
