<?php

namespace mindtwo\LaravelPlatformManager\Builders;

use Illuminate\Database\Eloquent\Builder;
use mindtwo\LaravelPlatformManager\Models\Platform;

class WebhookResponseBuilder extends Builder
{
    /**
     * Only processed WebhookRequests.
     */
    public function active(): WebhookResponseBuilder
    {
        return $this->where('active', true);
    }

    /**
     * Get all WebhookRequest for a platform.
     */
    public function forPlatform(Platform $platform): WebhookResponseBuilder
    {
        return $this->whereHas('platform', fn ($query) => $query->where('id', $platform->id));
    }
}
