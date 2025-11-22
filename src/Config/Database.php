<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Config;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database
{
    public static function initialize(): void
    {
        $capsule = new Capsule;

        // Support Railway MySQL connection string format
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
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}

