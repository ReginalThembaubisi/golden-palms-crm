<?php
/**
 * Database Schema Import Script
 * 
 * This script imports the database schema into your MySQL database.
 * 
 * Usage:
 * 1. Update the database credentials below
 * 2. Run: php database/import_schema.php
 */

// Database configuration
$db_host = 'localhost';
$db_name = 'goldenpalms_crm'; // Change this to your database name
$db_user = 'root';
$db_pass = ''; // Change this if you have a password

// Read the schema file
$schema_file = __DIR__ . '/schema.sql';

if (!file_exists($schema_file)) {
    die("Error: schema.sql file not found at: $schema_file\n");
}

// Connect to MySQL
try {
    $pdo = new PDO(
        "mysql:host=$db_host;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "✓ Connected to MySQL server\n";
    
    // Check if database exists, create if not
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$db_name' ready\n";
    
    // Select the database
    $pdo->exec("USE `$db_name`");
    echo "✓ Using database '$db_name'\n\n";
    
    // Read and execute the schema file
    $sql = file_get_contents($schema_file);
    
    if ($sql === false) {
        die("Error: Could not read schema file\n");
    }
    
    // Split SQL statements by semicolon, but preserve those inside quotes
    // Remove comments and empty lines
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
    
    // Split by semicolon, but be careful with semicolons inside strings
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*$/s', $stmt);
        }
    );
    
    $total = count($statements);
    $success = 0;
    $errors = 0;
    
    echo "Importing schema...\n";
    echo "Found $total SQL statements to execute\n\n";
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $success++;
            
            // Extract table name if it's a CREATE TABLE statement
            if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Created table: {$matches[1]}\n";
            } elseif (preg_match('/INSERT INTO `?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Inserted data into: {$matches[1]}\n";
            }
        } catch (PDOException $e) {
            $errors++;
            $error_msg = $e->getMessage();
            
            // Skip "already exists" errors for INSERT ... ON DUPLICATE KEY UPDATE
            if (strpos($error_msg, 'Duplicate entry') !== false) {
                // This is expected for ON DUPLICATE KEY UPDATE statements
                continue;
            }
            
            echo "✗ Error executing statement " . ($index + 1) . ": " . $error_msg . "\n";
            // Don't stop on errors, continue with other statements
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Import Summary:\n";
    echo "  Total statements: $total\n";
    echo "  Successful: $success\n";
    echo "  Errors: $errors\n";
    echo str_repeat("=", 50) . "\n";
    
    if ($errors === 0) {
        echo "\n✓ Database schema imported successfully!\n";
        echo "\nDefault login credentials:\n";
        echo "  Username: admin\n";
        echo "  Password: admin123\n";
        echo "\n⚠️  IMPORTANT: Change the admin password after first login!\n";
    } else {
        echo "\n⚠️  Some errors occurred. Please review the output above.\n";
    }
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}



