<?php

namespace App\Database;

use PDO;

class Connection
{
    public static function create(): PDO
    {
        $host = $_ENV['DB_HOST'];
        $port = $_ENV['DB_PORT'] ?? '3306';
        $database = $_ENV['DB_NAME'];

        $dsn = "mysql:host={$host};port={$port};" .
               "dbname={$database};charset=utf8mb4";

        return new PDO(
            $dsn,
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
}