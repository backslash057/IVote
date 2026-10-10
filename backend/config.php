<?php

require_once __DIR__ . '/bootstrap.php';

return [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: '3306',
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PASS'),
    'dbname'   => getenv('DB_NAME') ?: 'ivote',
];
