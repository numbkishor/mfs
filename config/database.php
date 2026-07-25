<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Database
 *
 * Thin singleton wrapper around a PDO connection. Using a single shared
 * PDO instance avoids reconnecting on every include while still keeping
 * every query going through prepared statements.
 */
final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                die('A database connection error occurred. Please try again later.');
            }
        }

        return self::$instance;
    }
}
