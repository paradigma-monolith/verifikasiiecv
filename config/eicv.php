<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Official E-ICV URL Prefixes
    |--------------------------------------------------------------------------
    | Scanned barcode/QR URLs must begin with one of these official
    | Ministry of Health (Kemenkes RI) / SINKARKES prefixes to be
    | considered authentic.
    */
    'allowed_prefixes' => [
        'https://sinkarkes.kemkes.go.id/welcome/check_document?t=',
        'https://www.sinkarkes.kemkes.go.id/welcome/check_document?t=',
        'https://kespel.depkes.go.id/',
        'https://www.kespel.depkes.go.id/',
        'https://www.sinkarkes.kemkes.go.id/index.php/welcome/check_document?t=',
        'https://www.sinkarkes.kemkes.go.id/',
        'https://sinkarkes.kemkes.go.id/',
        'https://sinkarkes.kemkes.go.id/index.php/welcome/check_document?t=',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Official Hosts (Defense-in-depth SSRF Protection)
    |--------------------------------------------------------------------------
    */
    'allowed_hosts' => [
        'sinkarkes.kemkes.go.id',
        'www.sinkarkes.kemkes.go.id',
        'kespel.depkes.go.id',
        'www.kespel.depkes.go.id',
    ],

    /*
    |--------------------------------------------------------------------------
    | SINKARKES Document Search Endpoint
    |--------------------------------------------------------------------------
    | Official AJAX endpoint called by SINKARKES check_document pages to
    | retrieve the certificate HTML body.
    */
    'search_endpoint' => env(
        'EICV_SEARCH_ENDPOINT',
        'https://sinkarkes.kemkes.go.id/welcome/check_document/search'
    ),

    /*
    |--------------------------------------------------------------------------
    | Known SINKARKES Document Types
    |--------------------------------------------------------------------------
    */
    'known_document_types' => [
        'icv',
        'icv-haji',
        'phqc',
        'izin_karantinaan',
        'health_book',
        'sscec',
        'pengujian_kesehatan',
        'pemeriksaan_obat_alkes_kapal',
        'sertifikat_air',
        'laik_terbang',
        'sanitasi_jasaboga',
        'sailing_permit',
        'izin_jenazah',
        'izin_sakit',
        'omkaba',
        'kontraindikasi',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    */
    'http_timeout' => (int) env('EICV_HTTP_TIMEOUT', 35),
    'connect_timeout' => (int) env('EICV_CONNECT_TIMEOUT', 15),
    'verify_ssl' => env('EICV_VERIFY_SSL', true),
    'ca_bundle' => env('EICV_CA_BUNDLE', storage_path('certs/cacert.pem')),
    'cache_ttl_seconds' => (int) env('EICV_CACHE_TTL', 600),
];
