<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AWS SNS (Simple Notification Service) for SMS functionality
    |--------------------------------------------------------------------------
    |
    | The given keys get the aws credentials from our env file what we have store
    |
    */

    'credentials' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],
    'region' => env('AWS_DEFAULT_REGION', 'eu-west-2'),
    'version' => 'latest',
];
