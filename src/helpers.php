<?php

use mindtwo\LaravelPlatformManager\Platform;

if (! function_exists('platform')) {
    function platform(): Platform
    {
        return resolve(Platform::class);
    }
}
