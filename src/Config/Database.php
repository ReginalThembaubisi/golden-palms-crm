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
        // Railway automatically sets MYSQL_URL when MySQL service is added
        $mysqlUrl = $_ENV['MYSQL_URL'] ?? $_ENV['DATABASE_URL'] ?? getenv('MYSQL_URL') ?? getenv('DATABASE_URL') ?? null;
        
        if ($mysqlUrl) {
            // Parse MySQL URL: mysql://user:password@host:port/database
            $parsed = parse_url($mysqlUrl);
            $host = $parsed['host'] ?? 'localhost';
            $port = $parsed['port'] ?? 3306;
            $database = ltrim($parsed['path'] ?? '/goldenpalms_crm', '/');
            $username = $parsed['user'] ?? 'root';
            $password = $parsed['pass'] ?? '';
            
            // Ensure we use TCP/IP, not socket (fix for Railway)
            if ($host === 'localhost' || $host === '127.0.0.1') {
                // For Railway, localhost should use 127.0.0.1 with explicit port
                $host = '127.0.0.1';
            }
            
            // Log for debugging (without sensitive info)
            error_log("Database connection: mysql://{$username}@{$host}:{$port}/{$database}");
            error_log("MYSQL_URL parsed - host: {$host}, port: {$port}, database: {$database}, username: {$username}");
        } else {
            // Use individual environment variables
            $host = $_ENV['DB_HOST'] ?? $_ENV['MYSQL_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? $_ENV['MYSQL_PORT'] ?? 3306;
            $database = $_ENV['DB_DATABASE'] ?? $_ENV['MYSQL_DATABASE'] ?? 'goldenpalms_crm';
            $username = $_ENV['DB_USERNAME'] ?? $_ENV['MYSQL_USER'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? $_ENV['MYSQL_PASSWORD'] ?? '';
            
            // Log for debugging
            error_log("Using individual DB variables - host: {$host}, port: {$port}, database: {$database}");
        }

        // Force TCP/IP connection - prevent socket file errors on Railway
        // Convert localhost to 127.0.0.1 to ensure TCP/IP is used
        $tcpHost = ($host === 'localhost') ? '127.0.0.1' : $host;
        
        // Ensure port is explicitly set (required for TCP/IP)
        $tcpPort = (int)$port;
        if ($tcpPort <= 0) {
            $tcpPort = 3306;
        }
        
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $tcpHost,
            'port' => $tcpPort,
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
                \PDO::ATTR_PERSISTENT => false,
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

