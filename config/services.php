<?php

return [
    'doccheck' => [
        'client_id' => env('DOCCHECK_CLIENT_KEY'),
        'client_secret' => env('DOCCHECK_CLIENT_SECRET'),
        'redirect' => env('DOCCHECK_REDIRECT_URI'),
        'language' => env('DOCCHECK_LANGUAGE', 'de'),
        'template' => env('DOCCHECK_TEMPLATE', 'fullscreen_dc'),
        'license' => env('DOCCHECK_LICENSE', 'economy'),
    ],
];
