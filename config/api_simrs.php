<?php

return [

    'base_url' => env(
        'API_BASE_URL',
        'http://127.0.0.1:8000'
    ),

    'token' => env(
        'API_TOKEN',
        ''
    ),

    'secret' => env(
        'API_SECRET',
        ''
    ),

    'timeout' => (int) env(
        'API_TIMEOUT',
        20
    ),

    'laboratorium' => [
        'hasil_endpoint' => '/api/service/gethasillaboratorium',
    ],

];