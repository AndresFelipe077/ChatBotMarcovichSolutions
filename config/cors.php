<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */


    'paths' => [
        'api/*',
        'sanctum/*',
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password',
        'email/verify/*',
        'email/verification-notification',
        'sanctum/csrf-cookie',
        'refresh-token',
        'api/refresh-token',
        'api/login',
        'api/logout',
        'api/register',
        'api/forgot-password',
        'api/reset-password',
        'api/email/verify/*',
        'api/email/verification-notification',
        'api/user',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Origin',
        'Content-Type',
        'X-Auth-Token',
        'Authorization',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-Socket-Id',
        'X-Socket-Auth',
        'X-XSRF-TOKEN',
        'Accept',
        'X-Requested-With',
        'X-Custom-Header',
        'X-Forwarded-For',
        'X-Forwarded-Host',
        'X-Forwarded-Proto',
    ],

    'exposed_headers' => [
        'Authorization',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'X-Auth-Status',
        'X-User-Id',
    ],

    'max_age' => 0,

    'supports_credentials' => false,

];
