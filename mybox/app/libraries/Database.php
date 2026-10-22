<?php
class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $database = APP_CONFIG['database'];
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $database['host'],
                $database['port'],
                $database['name']
            );

            self::$connection = new PDO($dsn, $database['user'], $database['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$connection;
    }
}
