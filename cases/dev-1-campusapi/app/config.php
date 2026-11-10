<?php
declare(strict_types=1);

// Intentionally invalid fixture credential. It matches a secret-scanner
// pattern but is not a real token and cannot authenticate anywhere.
return [
    'app_name' => 'CampusAPI',
    'api_token' => 'sk_test_51H_DUMMY_NOT_A_REAL_KEY',
    'log_level' => 'info',
    'max_page_size' => 50,
];
