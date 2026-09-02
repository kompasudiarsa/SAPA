<?php


return [
    'base_url' => env(
        'POLI_WAITING_API_BASE_URL',
        ''
    ),

    'endpoint' => env(
        'POLI_WAITING_API_ENDPOINT',
        '/api/waktu-tunggu-poli'
    ),

    'method' => env(
        'POLI_WAITING_API_METHOD',
        'GET'
    ),

    'patient_endpoint' => env(
        'POLI_WAITING_PATIENT_ENDPOINT',
        '/service/medifirst2000/reservasionline/get-pasien'
    ),

    'token' => env(
        'POLI_WAITING_API_TOKEN',
        ''
    ),

    'timeout' => (int) env(
        'POLI_WAITING_API_TIMEOUT',
        12
    ),
    'laboratory_endpoint' => env(
    'POLI_WAITING_LABORATORY_ENDPOINT',
    '/service/laboratorium/riwayat-order'
),
];

