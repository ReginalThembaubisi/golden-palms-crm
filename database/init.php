<?php
/**
 * Database Initialization Script for Railway
 * This script automatically creates the database schema on first deployment
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Initialize database connection
function initDatabase() {
    $capsule = new Capsule;
    
    // Support Railway MySQL connection string format
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
    } else {
        // Use individual environment variables
        // Railway uses MYSQLHOST (no underscore), also check MYSQL_HOST (with underscore)
        $host = '';
        $port = '';
        $database = '';
        $username = '';
        $password = '';
        
        // Helper to get env var from all sources
        $getEnvVar = function($names) {
            foreach ($names as $name) {
                if (isset($_ENV[$name]) && !empty($_ENV[$name])) {
                    return trim($_ENV[$name]);
                }
                if (isset($_SERVER[$name]) && !empty($_SERVER[$name])) {
                    return trim($_SERVER[$name]);
                }
                $value = getenv($name);
                if ($value !== false && !empty($value)) {
                    return trim($value);
                }
            }
            return '';
        };
        
        $host = $getEnvVar(['DB_HOST', 'MYSQL_HOST', 'MYSQLHOST']) ?: 'localhost';
        $port = $getEnvVar(['DB_PORT', 'MYSQL_PORT', 'MYSQLPORT']) ?: '3306';
        $database = $getEnvVar(['DB_DATABASE', 'MYSQL_DATABASE']) ?: 'goldenpalms_crm';
        $username = $getEnvVar(['DB_USERNAME', 'MYSQL_USER', 'MYSQLUSER']) ?: 'root';
        $password = $getEnvVar(['DB_PASSWORD', 'MYSQL_PASSWORD', 'MYSQLPASSWORD']) ?: '';
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
    
    return $capsule;
}

try {
    echo "Initializing database connection...\n";
    $capsule = initDatabase();
    
    // Check if users table exists (indicates schema is already set up)
    try {
        $tablesExist = Capsule::schema()->hasTable('users');
        
        if ($tablesExist) {
            echo "✓ Database schema already exists. Skipping initialization.\n";
            exit(0);
        }
    } catch (\Exception $e) {
        // Table check failed, might be first run - continue with initialization
        echo "Database not initialized. Proceeding with setup...\n";
    }
    
    echo "Database schema not found. Creating tables...\n";
    
    // Read and execute schema.sql
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Remove comments and split into statements
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && strlen(trim($stmt)) > 10; // Filter out empty/short statements
        }
    );
    
    $executed = 0;
    $pdo = Capsule::connection()->getPdo();
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate') === false) {
                echo "Warning: " . substr($e->getMessage(), 0, 100) . "\n";
            }
        }
    }
    
    echo "✓ Database schema created successfully! ($executed statements executed)\n";
    
    // Verify tables were created
    $tables = ['users', 'leads', 'bookings', 'guests', 'units'];
    $allExist = true;
    foreach ($tables as $table) {
        if (!Capsule::schema()->hasTable($table)) {
            echo "Warning: Table '$table' was not created.\n";
            $allExist = false;
        }
    }
    
    if ($allExist) {
        echo "✓ All required tables verified.\n";
    }
    
    exit(0);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

