<?php

namespace mindtwo\LaravelPlatformManager\Services;

use Illuminate\Http\Request;
use mindtwo\LaravelPlatformManager\Models\Platform;

class PlatformResolver
{
    /**
     * Current platform for host.
     *
     * @var Platform|null
     */
    private ?Platform $current = null;

    public function __construct(
        protected Request $request,
    ) {
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

        if ($this->request->hasHeader('X-Platform-Public-Auth-Token')) {
            $this->current = $model->resolveByPublicAuthToken($this->request->header('X-Platform-Public-Auth-Token'));
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
