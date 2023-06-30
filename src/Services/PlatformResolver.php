<?php

namespace mindtwo\LaravelPlatformManager\Services;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
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
     * Platform only has to be active. When mode is strict
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
                        ->where('is_active', true);
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
            $this->current = $this->platformModel::query()->isActive()->byPublicAuthToken($token)->first();
        }

        if (($headerName = AuthTokenTypeEnum::Secret->getHeaderName()) && is_string($token = $this->request->header($headerName))) {

            $this->current = $this->platformModel::query()->isActive()->bySecretAuthToken($token)->first();
        }

        // Check for hostname
        if (empty($this->current)) {
            $this->current = $this->platformModel::query()->isActive()->byHostname($this->request->getHost())->first();
        }

        // Fallback primary platform
        if (empty($this->current)) {
            try {
                $this->current = $this->platformModel::query()->isActive()->isMain()->firstOrFail();
            } catch (\Throwable $th) {
                throw new HttpResponseException(response('No platform found.', 404));
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
