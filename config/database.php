<?php

function getDB(): mysqli {
    static $db  = null;
    static $env = null;
    if ($db !== null) return $db;
    if ($env === null) {
        $env = parse_ini_file(__DIR__ . '/../.env') ?: [];
    }
    $db = new mysqli(
        $env['DB_HOST'] ?? 'localhost',
        $env['DB_USER'] ?? '',
        $env['DB_PASS'] ?? '',
        $env['DB_NAME'] ?? ''
    );
    if ($db->connect_error) {
        die("Database connection failed: " . $db->connect_error);
    }
    return $db;
}
