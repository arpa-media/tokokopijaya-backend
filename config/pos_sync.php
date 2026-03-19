<?php

return [
    'seeders' => [
        'enable_demo_users' => env('POS_ENABLE_DEMO_USERS', false),
        'enable_demo_outlets' => env('POS_ENABLE_DEMO_OUTLETS', false),
        'seed_master_data' => env('POS_SEED_MASTER_DATA', true),
    ],

    'roles' => [
        'protected' => array_values(array_filter(array_map('trim', explode(',', (string) env('POS_PROTECTED_ROLE_NAMES', 'admin'))))),
        'sync_assignments' => env('POS_SYNC_ASSIGN_DEFAULT_ROLES', true),
        'classification_map' => [
            'squad' => 'cashier',
            'management' => 'manager',
            'warehouse' => 'warehouse',
            'legacy' => 'cashier',
        ],
    ],

    'auth' => [
        // false = mode transisi, legacy fallback backoffice masih diizinkan.
        // true = login backoffice wajib punya HR-shaped context (employee + assignment/outlet).
        'require_hr_assignment' => env('POS_AUTH_REQUIRE_HR_ASSIGNMENT', false),
    ],

    'legacy_bridge' => [
        // Sinkronkan users.outlet_id dari resolved HR auth context setelah import.
        'sync_on_import' => env('POS_SYNC_LEGACY_USER_OUTLET_BRIDGE', true),
        // Jika true, squad akan tetap diset ke outlet assignment agar modul lama yang masih membaca users.outlet_id tetap aman.
        'mirror_squad_assignment_outlet' => env('POS_MIRROR_SQUAD_ASSIGNMENT_OUTLET', true),
        // Jika false, user non-squad akan dinullkan users.outlet_id agar tidak ada salah scope diam-diam.
        'keep_non_squad_outlet_id' => env('POS_KEEP_NON_SQUAD_LEGACY_OUTLET_ID', false),
    ],
];
