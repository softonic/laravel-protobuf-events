<?php

use Psr\Log\LogLevel;

return [
    /*
    |--------------------------------------------------------------------------
    | Client Identifier to use as origin in a protobuf communication. Allows to isolate different services
    |--------------------------------------------------------------------------
    */
    'client' => env('RABBITEVENTS_CLIENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Log Level for the incoming and outgoing logs.
    |--------------------------------------------------------------------------
    */
    'communications_log_level' => LogLevel::INFO,
];
