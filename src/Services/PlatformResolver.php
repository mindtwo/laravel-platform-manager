<?php

namespace mindtwo\LaravelPlatformManager\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
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

        $model = app(config('platform-resolver.model'));

        try {
            $this->current = $model->query()->byHostname($this->request->getHost())->firstOrFail();
        } catch (ModelNotFoundException $e) {
            $this->current = $model->query()->isMain()->firstOrFail();
        }

        return $this->current;
    }
}
