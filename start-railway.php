<?php
/**
 * Railway start script - reads PORT from environment and starts PHP server
 * This bypasses shell variable expansion issues
 */

// Get PORT from environment (Railway sets this)
$port = $_SERVER['PORT'] ?? $_ENV['PORT'] ?? getenv('PORT') ?: 8080;

// Validate port is numeric
if (!is_numeric($port)) {
    error_log("ERROR: PORT is not numeric: " . var_export($port, true));
    error_log("Available environment variables: " . implode(', ', array_keys($_SERVER)));
    exit(1);
}

$host = '0.0.0.0';
$root = __DIR__;
$router = __DIR__ . '/index.php';

error_log("Starting PHP server on $host:$port");
error_log("Document root: $root");
error_log("Router: $router");

// Build command
$command = sprintf('php -S %s:%d -t %s %s', $host, $port, $root, $router);

error_log("Executing: $command");

// Execute the command
passthru($command);

