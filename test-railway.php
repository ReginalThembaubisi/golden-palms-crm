<?php
/**
 * Simple test script for Railway deployment
 * Visit: https://your-app.up.railway.app/test-railway.php
 */

header('Content-Type: text/plain');

echo "=== Railway Deployment Test ===\n\n";

// Test 1: PHP Version
echo "1. PHP Version: " . PHP_VERSION . "\n";

// Test 2: Required Extensions
$required = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'curl'];
echo "\n2. PHP Extensions:\n";
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo "   - $ext: " . ($loaded ? "✓" : "✗") . "\n";
}

// Test 3: Environment Variables
echo "\n3. Environment Variables:\n";
$envVars = ['PORT', 'APP_URL', 'APP_SECRET', 'MYSQL_URL', 'DB_HOST'];
foreach ($envVars as $var) {
    $value = $_ENV[$var] ?? getenv($var) ?? 'NOT SET';
    if ($var === 'MYSQL_URL' || $var === 'APP_SECRET') {
        $value = $value !== 'NOT SET' ? 'SET (hidden)' : 'NOT SET';
    }
    echo "   - $var: $value\n";
}

// Test 4: File System
echo "\n4. File System:\n";
$files = ['index.php', 'composer.json', 'vendor/autoload.php', 'public/index.html'];
foreach ($files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo "   - $file: " . ($exists ? "✓" : "✗") . "\n";
}

// Test 5: Database Connection (if MYSQL_URL is set)
echo "\n5. Database Connection:\n";
$mysqlUrl = $_ENV['MYSQL_URL'] ?? getenv('MYSQL_URL');
if ($mysqlUrl) {
    try {
        $parsed = parse_url($mysqlUrl);
        $host = $parsed['host'] ?? 'unknown';
        $db = ltrim($parsed['path'] ?? '/unknown', '/');
        echo "   - MYSQL_URL detected: mysql://...@$host/$db\n";
        
        // Try to connect
        $pdo = new PDO(
            "mysql:host={$parsed['host']};port=" . ($parsed['port'] ?? 3306) . ";dbname=" . ltrim($parsed['path'] ?? '/test', '/'),
            $parsed['user'] ?? 'root',
            $parsed['pass'] ?? ''
        );
        echo "   - Connection: ✓ SUCCESS\n";
    } catch (Exception $e) {
        echo "   - Connection: ✗ FAILED - " . $e->getMessage() . "\n";
    }
} else {
    echo "   - MYSQL_URL: NOT SET (add MySQL service in Railway)\n";
}

// Test 6: Composer Autoload
echo "\n6. Composer Autoload:\n";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
    echo "   - Autoload: ✓ LOADED\n";
} else {
    echo "   - Autoload: ✗ NOT FOUND\n";
}

echo "\n=== Test Complete ===\n";
echo "\nIf all tests pass, your app should work!\n";
echo "If tests fail, check Railway logs for details.\n";

