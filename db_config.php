<?php
// db_config.php

// TODO: change these four values to match your cPanel DB setup
$DB_HOST = "localhost";              // usually 'localhost'
$DB_NAME = "welcgyze_welcomebackto";          // your database name
$DB_USER = "welcgyze_welcomebackto_user";     // your database user
$DB_PASS = "34iE@poZDNZy6eL";  // your database password

/**
 * Get a shared PDO instance.
 */
function get_pdo(): PDO {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;

    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    }

    return $pdo;
}
