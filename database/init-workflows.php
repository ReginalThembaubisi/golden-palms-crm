<?php

/**
 * Workflow Tables Migration Script
 * Run this script to add workflow automation tables to your database
 * 
 * Usage:
 *   php database/init-workflows.php
 *   OR
 *   Access via browser: http://your-domain/database/init-workflows.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use GoldenPalms\CRM\Config\Database;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Initialize database
try {
    Database::initialize();
    echo "✓ Database connection established\n";
} catch (Exception $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Check if workflows table already exists
$workflowsExists = DB::schema()->hasTable('workflows');
$executionsExists = DB::schema()->hasTable('workflow_executions');

if ($workflowsExists && $executionsExists) {
    echo "✓ Workflow tables already exist\n";
    echo "  - workflows table: ✓\n";
    echo "  - workflow_executions table: ✓\n";
    echo "\n";
    echo "Would you like to:\n";
    echo "  1. Re-run migration (will fail if tables exist)\n";
    echo "  2. Just verify tables\n";
    echo "  3. Exit\n";
    echo "\n";
    
    if (php_sapi_name() === 'cli') {
        echo "Enter choice (1-3): ";
        $choice = trim(fgets(STDIN));
    } else {
        // Web interface
        $choice = $_GET['action'] ?? '2';
    }
    
    if ($choice === '1') {
        echo "Dropping existing tables...\n";
        DB::schema()->dropIfExists('workflow_executions');
        DB::schema()->dropIfExists('workflows');
        echo "✓ Tables dropped\n";
    } elseif ($choice === '3') {
        exit("Exiting...\n");
    }
}

// Read migration SQL
$migrationFile = __DIR__ . '/migrations/create_workflows_table.sql';
if (!file_exists($migrationFile)) {
    die("✗ Migration file not found: {$migrationFile}\n");
}

$sql = file_get_contents($migrationFile);

// Split SQL into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && 
               !preg_match('/^\s*--/', $stmt) && 
               !preg_match('/^\s*$/', $stmt);
    }
);

echo "\n";
echo "Running migration...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$success = true;
$executed = 0;

foreach ($statements as $statement) {
    // Skip comments and empty statements
    if (empty(trim($statement)) || preg_match('/^\s*--/', $statement)) {
        continue;
    }
    
    try {
        // Execute statement
        DB::unprepared($statement);
        $executed++;
        
        // Extract table name if CREATE TABLE
        if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
            $tableName = $matches[1] ?? 'unknown';
            echo "✓ Created table: {$tableName}\n";
        } elseif (preg_match('/INSERT INTO.*?`?(\w+)`?/i', $statement, $matches)) {
            $tableName = $matches[1] ?? 'unknown';
            echo "✓ Inserted default data into: {$tableName}\n";
        } else {
            echo "✓ Executed SQL statement\n";
        }
    } catch (Exception $e) {
        // Check if it's a "table already exists" error
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠ Table already exists (skipping)\n";
        } else {
            echo "✗ Error: " . $e->getMessage() . "\n";
            $success = false;
        }
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";

if ($success) {
    echo "✓ Migration completed successfully!\n";
    echo "  - Executed {$executed} statements\n";
    echo "\n";
    
    // Verify tables
    echo "Verifying tables...\n";
    $workflowsExists = DB::schema()->hasTable('workflows');
    $executionsExists = DB::schema()->hasTable('workflow_executions');
    
    if ($workflowsExists && $executionsExists) {
        echo "✓ workflows table exists\n";
        echo "✓ workflow_executions table exists\n";
        
        // Count workflows
        $workflowCount = DB::table('workflows')->count();
        echo "✓ Found {$workflowCount} workflow(s)\n";
        
        // List workflows
        $workflows = DB::table('workflows')->select('id', 'name', 'trigger_type', 'is_active')->get();
        if ($workflows->count() > 0) {
            echo "\n";
            echo "Workflows:\n";
            foreach ($workflows as $wf) {
                $status = $wf->is_active ? '✓ Active' : '✗ Inactive';
                echo "  [{$wf->id}] {$wf->name} ({$wf->trigger_type}) - {$status}\n";
            }
        }
        
        echo "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✓ Integration complete!\n";
        echo "\n";
        echo "Next steps:\n";
        echo "  1. Test dashboard API: GET /api/dashboard/stats\n";
        echo "  2. Create a test lead to see automatic scoring\n";
        echo "  3. Check workflow_executions table for automation logs\n";
        echo "\n";
    } else {
        echo "✗ Verification failed - tables not found\n";
    }
} else {
    echo "✗ Migration completed with errors\n";
    echo "  Please check the error messages above\n";
}


