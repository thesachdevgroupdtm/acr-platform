<?php

return [
    /*
     * Bearer token required to call the CSV import endpoints.
     * Sent as: Authorization: Bearer {token}
     */
    'token' => env('IMPORT_API_TOKEN', 'dev-import-token-change-me'),

    /*
     * Maximum upload size in kilobytes for CSV files.
     */
    'max_kb' => env('IMPORT_MAX_KB', 10240),
];
