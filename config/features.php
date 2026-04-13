<?php

return [
    'strict' => env('FEATURE_FLAGS_STRICT', false),

    'cache_ttl' => env('FEATURE_FLAGS_CACHE_TTL', 300),

    'registry' => [
        'new_dashboard' => [
            'default' => false,
            'name' => 'New Dashboard',
            'description' => 'New dashboard UI',
        ],

        // Keep this enabled by default to avoid behavior changes in existing flows.
        'employee_of_month' => [
            'default' => true,
            'name' => 'Employee Of Month Module',
            'description' => 'Employee of month voting and admin pages',
        ],
    ],
];
