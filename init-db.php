<?php
/**
 * Manual database initialization script for Railway
 * Run this if auto-init doesn't work
 * 
 * Usage: php init-db.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

echo "=== Database Initialization Script ===\n\n";

// Get database connection from environment
$mysqlUrl = $_ENV['MYSQL_URL'] ?? $_ENV['DATABASE_URL'] ?? getenv('MYSQL_URL') ?? getenv('DATABASE_URL') ?? null;

if (!$mysqlUrl) {
    echo "ERROR: MYSQL_URL not found in environment!\n";
    echo "Available env vars: " . implode(', ', array_keys($_ENV)) . "\n";
    exit(1);
}

echo "Found MYSQL_URL\n";

// Parse MySQL URL
$parsed = parse_url($mysqlUrl);
$host = $parsed['host'] ?? 'localhost';
$port = $parsed['port'] ?? 3306;
$database = ltrim($parsed['path'] ?? '/goldenpalms_crm', '/');
$username = $parsed['user'] ?? 'root';
$password = $parsed['pass'] ?? '';

echo "Connecting to: mysql://{$username}@{$host}:{$port}/{$database}\n\n";

try {
    $capsule = new Capsule;
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => $host,
        'port' => $port,
        'database' => $database,
        'username' => $username,
        'password' => $password,
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ]);
    
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    
    // Test connection
    $capsule->connection()->getPdo();
    echo "✓ Database connection successful!\n\n";
    
    // Check if tables exist
    if ($capsule->schema()->hasTable('users')) {
        echo "✓ Database already initialized (tables exist)\n";
        exit(0);
    }
    
    echo "Database is empty. Initializing...\n\n";
    
    // Read schema file
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Remove comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Split into statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && strlen(trim($stmt)) > 10;
        }
    );
    
    $pdo = $capsule->connection()->getPdo();
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $executed++;
            if ($executed % 10 == 0) {
                echo "  Executed $executed statements...\n";
            }
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate') === false) {
                echo "  Warning: " . substr($e->getMessage(), 0, 100) . "\n";
                $errors++;
            }
        }
    }
    
    echo "\n✓ Database initialization complete!\n";
    echo "  - Executed: $executed statements\n";
    if ($errors > 0) {
        echo "  - Warnings: $errors\n";
    }
    
    // Verify tables
    $tables = ['users', 'leads', 'bookings', 'guests', 'units'];
    echo "\nVerifying tables:\n";
    foreach ($tables as $table) {
        if ($capsule->schema()->hasTable($table)) {
            echo "  ✓ $table\n";
        } else {
            echo "  ✗ $table (missing)\n";
        }
    }
    
    echo "\n=== Done! ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

