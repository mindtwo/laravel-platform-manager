<?php

return [

    /**
     * Set platform model
     */
    'model' => \mindtwo\LaravelPlatformManager\Models\Platform::class,

    /**
     * Settings DTO class. Extend PlatformSettings to declare typed properties
     * and mark sensitive ones for encryption via $encrypted.
     */
    'settings' => \mindtwo\LaravelPlatformManager\Settings\PlatformSettings::class,

    /**
     * Header Names used to retrieve platform
     */
    'header_names' => [
        'token' => 'X-Platform-Token',

        /**
         * Legacy token header accepted during grace period.
         * Set to null to disable.
         */
        'token_legacy' => 'X-Context-Platform-Public-Auth-Token',
    ],

    /**
     * Session key used to store and resolve the platform ID.
     * Used by saveToSession() and the 'session' resolver strategy.
     */
    'session_key' => 'platform_id',

];
