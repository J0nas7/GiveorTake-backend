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

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://192.168.50.132:3000',
        'https://giveortake-nextjs-frontend.ey.r.appspot.com',
        'https://nsgiveortakenextjsfr83wtqlnv-container-giveortake-nextjs-fronte.functions.fnc.fr-par.scw.cloud',
        'https://giveortake.jonas-alexander.dk',
        'https://giveortake-nextjs-frontend-3kv8zehgn-j0nas7s-projects.vercel.app',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
