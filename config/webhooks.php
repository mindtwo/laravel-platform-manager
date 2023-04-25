<?php

return [

    'example' => [

        /**
         * Validation rules for received data
         */
        'rules' => [
            'foo' => 'string',
        ],

        /**
         * Closure, invokeable instance or invokeable class.
         * Called after webhook was received and stored in database. The
         * WebhookRequest instace gets passed as parameter.
         *
         * Data returned by this function will be included
         * in response.
         */
        'responseCallback' => null,

        // TODO maybe exclude platforms?
        'exclude' => [],
    ],

];
