<?php

return [
    // Relative path from the app directory to api controllers directory.
    'controllers_dir' => env('PHPSA_API_CONTROLLER_DIR', 'Http/Controllers/Api'),
    // Relative path from the app directory to the api routes file.
    'routes_file' => '../routes/api.php',
    // Relative path from the app directory to the models directory. Typically it's either 'Models' or ''.
    'models_base_dir' => 'Models',
    // Relative path from the base directory to the api controller stub.
    'controller_stub' => env('PHPSA_API_CONTROLLER_STUB', 'vendor/phpsa/laravel-api-controller/src/Generator/stubs/controller.stub'),
    // Relative path from the base directory to the route stub.
    'route_stub' => env('PHPSA_API_ROUTE_STUB', 'vendor/phpsa/laravel-api-controller/src/Generator/stubs/route.stub'),

    'cache_table_columns' => env('PHPSA_API_CACHE_TABLE_COLUMNS', false), // Cache table columns
    'cache_table_columns_ttl' => env('PHPSA_API_CACHE_TABLE_COLUMNS_TTL', 60 * 60 * 24), // Cache table columns ttl
    'cache_table_columns_prefix' => env('PHPSA_API_CACHE_TABLE_COLUMNS_PREFIX', 'phpsa_table_columns_'), // Cache table columns prefix

    'parameters' => [
        'include' => 'include', // which hasOnes / HasMany etc to include in the response
        'filter' => 'filter', // filter on fields
        'sort' => 'sort', // sort the response
        'fields' => 'fields', // fields to return
        'page' => 'page', //Page number when pagination is on
        'group' => 'group', // Group by query
        'addfields' => 'addfields', //Add fields to the default fields
        'removefields' => 'removefields', //Remove fields from the default fields
        'limit' => 'limit', // howe many records to return
    ],
];
 