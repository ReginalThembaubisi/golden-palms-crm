<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Config;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database
{
    public static function initialize(): void
    {
        $capsule = new Capsule;

        // Support Railway MySQL and Render PostgreSQL connection string formats
        $mysqlUrl = $_ENV['MYSQL_URL'] ?? $_ENV['DATABASE_URL'] ?? null;
        
        if ($mysqlUrl) {
            // Parse MySQL URL: mysql://user:password@host:port/database
            $parsed = parse_url($mysqlUrl);
            $host = $parsed['host'] ?? 'localhost';
            $port = $parsed['port'] ?? 3306;
            $database = ltrim($parsed['path'] ?? '/goldenpalms_crm', '/');
            $username = $parsed['user'] ?? 'root';
            $password = $parsed['pass'] ?? '';
        } else {
            // Use individual environment variables
            $host = $_ENV['DB_HOST'] ?? $_ENV['MYSQL_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? $_ENV['MYSQL_PORT'] ?? 3306;
            $database = $_ENV['DB_DATABASE'] ?? $_ENV['MYSQL_DATABASE'] ?? 'goldenpalms_crm';
            $username = $_ENV['DB_USERNAME'] ?? $_ENV['MYSQL_USER'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? $_ENV['MYSQL_PASSWORD'] ?? '';
        }

        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        
        // Test connection immediately to catch errors early
        try {
            $capsule->connection()->getPdo();
        } catch (\PDOException $e) {
            throw new \RuntimeException('Failed to connect to database: ' . $e->getMessage(), 0, $e);
        }
    }
}

