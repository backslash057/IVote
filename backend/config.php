<?php

return [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'username' => getenv('DB_USER') ?: 'backslash057',
    'password' => getenv('DB_PASS') ?: 'root',
    'dbname'   => getenv('DB_NAME') ?: 'ivote',
];

?>
