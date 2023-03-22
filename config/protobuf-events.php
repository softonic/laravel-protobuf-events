<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Client Identifier to use as origin in a protobuf communication. Allows to isolate different services
    |--------------------------------------------------------------------------
    */
    'client' => env('RABBITEVENTS_CLIENT_ID'),
];
