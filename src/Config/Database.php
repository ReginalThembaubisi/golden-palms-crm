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
        // Check all possible sources for MYSQL_URL
        $mysqlUrl = null;
        
        // Try $_ENV first (most common)
        if (isset($_ENV['MYSQL_URL']) && !empty($_ENV['MYSQL_URL'])) {
            $mysqlUrl = $_ENV['MYSQL_URL'];
        } elseif (isset($_ENV['DATABASE_URL']) && !empty($_ENV['DATABASE_URL'])) {
            $mysqlUrl = $_ENV['DATABASE_URL'];
        } elseif (getenv('MYSQL_URL') !== false && !empty(getenv('MYSQL_URL'))) {
            $mysqlUrl = getenv('MYSQL_URL');
        } elseif (getenv('DATABASE_URL') !== false && !empty(getenv('DATABASE_URL'))) {
            $mysqlUrl = getenv('DATABASE_URL');
        }
        
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
            // Check multiple sources: $_ENV, $_SERVER, getenv() (Railway might use different sources)
            // Try DB_* first, then MYSQL_* (Railway's format)
            
            // Helper function to get env var from all sources
            $getEnvVar = function($names) {
                foreach ($names as $name) {
                    // Try $_ENV
                    if (isset($_ENV[$name]) && !empty($_ENV[$name])) {
                        return trim($_ENV[$name]);
                    }
                    // Try $_SERVER
                    if (isset($_SERVER[$name]) && !empty($_SERVER[$name])) {
                        return trim($_SERVER[$name]);
                    }
                    // Try getenv()
                    $value = getenv($name);
                    if ($value !== false && !empty($value)) {
                        return trim($value);
                    }
                }
                return '';
            };
            
            $host = $getEnvVar(['DB_HOST', 'MYSQL_HOST']);
            $port = $getEnvVar(['DB_PORT', 'MYSQL_PORT']);
            $database = $getEnvVar(['DB_DATABASE', 'MYSQL_DATABASE']);
            $username = $getEnvVar(['DB_USERNAME', 'MYSQL_USER']);
            $password = $getEnvVar(['DB_PASSWORD', 'MYSQL_PASSWORD']);
            
            // Debug: Log what we found
            error_log("Checking environment variables:");
            error_log("  \$_ENV['MYSQL_HOST']: " . (isset($_ENV['MYSQL_HOST']) ? 'set (' . $_ENV['MYSQL_HOST'] . ')' : 'not set'));
            error_log("  \$_SERVER['MYSQL_HOST']: " . (isset($_SERVER['MYSQL_HOST']) ? 'set (' . $_SERVER['MYSQL_HOST'] . ')' : 'not set'));
            error_log("  getenv('MYSQL_HOST'): " . (getenv('MYSQL_HOST') !== false ? getenv('MYSQL_HOST') : 'not set'));
            
            // List all MYSQL_* variables found
            $mysqlEnvVars = array_filter(array_keys($_ENV), fn($k) => strpos($k, 'MYSQL_') === 0);
            $mysqlServerVars = array_filter(array_keys($_SERVER), fn($k) => strpos($k, 'MYSQL_') === 0);
            error_log("  All MYSQL_* vars in \$_ENV: " . (empty($mysqlEnvVars) ? 'none' : implode(', ', $mysqlEnvVars)));
            error_log("  All MYSQL_* vars in \$_SERVER: " . (empty($mysqlServerVars) ? 'none' : implode(', ', $mysqlServerVars)));
            
            // Validate required values
            if (empty($host)) {
                error_log("ERROR: DB_HOST/MYSQL_HOST is empty! Available env vars: " . implode(', ', array_keys($_ENV)));
                throw new \RuntimeException('DB_HOST/MYSQL_HOST environment variable is not set. Please configure database connection in Railway.');
            }
            
            if (empty($port)) {
                $port = '3306';
                error_log("WARNING: DB_PORT/MYSQL_PORT not set, using default 3306");
            }
            
            if (empty($database)) {
                error_log("ERROR: DB_DATABASE/MYSQL_DATABASE is empty!");
                throw new \RuntimeException('DB_DATABASE/MYSQL_DATABASE environment variable is not set. Please configure database connection in Railway.');
            }
            
            // Log for debugging
            error_log("Using individual DB variables - host: {$host}, port: {$port}, database: {$database}, username: {$username}");
            
            // Warn if using localhost (won't work on Railway)
            if ($host === 'localhost') {
                error_log("WARNING: Using localhost as DB_HOST - this won't work on Railway! Set DB_HOST to Railway MySQL hostname.");
            }
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

