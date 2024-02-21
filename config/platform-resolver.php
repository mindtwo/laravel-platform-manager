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

    /**
     * Settings regarding Webhook handling and activation.
     * See seperate webhook config to define webhooks.
     */
    'webhooks' => [

        /**
         * The queue name for the webhook.
         *
         * default: 'default'
         */
        'default_queue' => 'default',

        /**
         * The default timeout for the webhook.
         *
         * default: 30
         */
        'default_timeout' => 30,

        /**
         * Enable or disable whether this application
         * can receive webhook requests.
         *
         * default: false
         */
        'enabled' => false,

        /**
         * Endpoint which listens for Webhooks.
         *
         * default: '/v1/webhooks'
         */
        'endpoint' => '/v1/webhooks',
    ],

    'default_locale' => 'en-EN',

    'available_locales' => [
        'en-EN',
    ],
];
