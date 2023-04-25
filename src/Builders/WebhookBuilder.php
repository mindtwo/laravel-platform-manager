<?php

namespace mindtwo\LaravelPlatformManager\Builders;

use Illuminate\Database\Eloquent\Builder;
use mindtwo\LaravelPlatformManager\Models\Platform;

class WebhookBuilder extends Builder
{
    /**
     * Only processed webhooks.
     */
    public function processed(): WebhookBuilder
    {
        return $this->whereNotNull('processed_at');
    }

    /**
     * Get all webhook requests for a platform.
     */
    public function forPlatform(Platform $platform): WebhookBuilder
    {
        return $this->whereHas('platform', fn ($query) => $query->where('id', $platform->id));
    }
}
