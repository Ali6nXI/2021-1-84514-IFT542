<?php
declare(strict_types=1);

function db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $databaseUser = getenv('IFT542_DB_USER');
    $databasePassword = getenv('IFT542_DB_PASSWORD');

    if (
        $databaseUser === false
        || $databasePassword === false
    ) {
        error_log('Database environment variables are not configured.');

        throw new RuntimeException(
            'Database configuration unavailable.'
        );
    }

    $connection = new PDO(
        'mysql:host=127.0.0.1;dbname=ift542_app;charset=utf8mb4',
        $databaseUser,
        $databasePassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $connection;
}