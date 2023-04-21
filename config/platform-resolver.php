<?php

return [

    /**
     * Set platform model
     */
    'model' => \mindtwo\LaravelPlatformManager\Models\Platform::class,

    /**
     *  Header Names used to retrieve platform
     */
    'headerNames' => [
        \mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum::Public() => 'X-Context-Platform-Public-Auth-Token',
        \mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum::Secret() => 'X-Context-Platform-Secret-Auth-Token',
    ],
];
