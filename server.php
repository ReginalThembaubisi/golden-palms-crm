<?php
/**
 * Railway-compatible PHP server script
 * Reads PORT from environment and starts PHP built-in server
 */

$port = $_SERVER['PORT'] ?? $_ENV['PORT'] ?? getenv('PORT') ?: 8080;
$host = '0.0.0.0';
$root = __DIR__;
$router = __DIR__ . '/index.php';

echo "Starting server on $host:$port\n";
echo "Document root: $root\n";
echo "Router: $router\n";

// Start the server
exec("php -S $host:$port -t $root $router");

