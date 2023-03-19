<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Client Identifier to use as origin in a protobuf communication. Allows to isolate different services
    | Ex: env('SITE', 'global') . '_' . applicationName()
    |--------------------------------------------------------------------------
    */
    'client' => env('RABBITEVENTS_CLIENT_ID', 'laravel-protobuf-events'),
];
