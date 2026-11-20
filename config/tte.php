<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TTE Auto Numbering Feature Flag
    |--------------------------------------------------------------------------
    |
    | When enabled, letter numbers will be automatically generated using
    | predefined letter types, destinations, units, and year-based blocks.
    | When disabled, the legacy manual input form remains active.
    |
    */

    'auto_numbering' => (bool) env('TTE_AUTO_NUMBERING', false),

    /*
    |--------------------------------------------------------------------------
    | Documents Upload & Finalization Feature Flag
    |--------------------------------------------------------------------------
    |
    | When enabled, users can upload final PDF documents to lock letters.
    | When disabled, document routes and upload UI are hidden/inactive.
    |
    */

    'documents_enabled' => (bool) env('TTE_DOCUMENTS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Sequence Number Padding
    |--------------------------------------------------------------------------
    |
    | Digit padding for sequence numbers (e.g. 0 = "5", 3 = "005").
    | Default is 0 (no padding).
    |
    */

    'number_pad' => (int) env('TTE_NUMBER_PAD', 0),

    /*
    |--------------------------------------------------------------------------
    | Document Upload Limits
    |--------------------------------------------------------------------------
    |
    | Maximum allowed PDF file size in kilobytes (default 5120 KB = 5 MB).
    |
    */

    'document_max_kb' => (int) env('TTE_DOCUMENT_MAX_KB', 5120),

    /*
    |--------------------------------------------------------------------------
    | Storage Disk for Final Documents
    |--------------------------------------------------------------------------
    |
    | Disk used to store private final PDF documents.
    | Default is 'local' (storage/app/private).
    |
    */

    'document_disk' => env('TTE_DOCUMENT_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | QR Link Feature Flag
    |--------------------------------------------------------------------------
    |
    | When enabled, staff can turn an external link (e.g. Google Drive) into a
    | QR code with the hospital logo. Scanning the QR redirects to the target.
    | WARNING: once QR codes are printed, do not switch this off again because
    | the public redirect route (/l/{code}) is disabled together with the menu.
    |
    */

    'qr_link_enabled' => (bool) env('TTE_QR_LINK_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | QR Link Allowed Target Hosts
    |--------------------------------------------------------------------------
    |
    | Comma separated list of hosts that may be used as QR Link targets.
    | Exact host match only (subdomains are NOT allowed implicitly) and only
    | the https scheme is accepted. Prevents the redirect from being abused.
    |
    */

    'qr_link_allowed_hosts' => array_values(array_filter(array_map(
        fn (string $host) => strtolower(trim($host)),
        explode(',', (string) env('TTE_QR_LINK_ALLOWED_HOSTS', 'drive.google.com,docs.google.com'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | QR Link Code Length
    |--------------------------------------------------------------------------
    |
    | Length of the random short code in /l/{code}. Do not change after QR
    | codes have been printed; the public route pattern assumes this length.
    |
    */

    'qr_link_code_length' => 10,

];

