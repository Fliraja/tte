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

];

