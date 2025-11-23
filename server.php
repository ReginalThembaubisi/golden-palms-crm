<?php
/**
 * Railway-compatible PHP server script
 * Reads PORT from environment and starts PHP built-in server
 */

$port = $_SERVER['PORT'] ?? $_ENV['PORT'] ?? getenv('PORT') ?: 8080;
$host = '0.0.0.0';
$root = __DIR__;
$router = __DIR__ . '/index.php';

// Build command
$command = "php -S $host:$port -t $root $router";

// Output for debugging
error_log("Starting PHP server: $command");
error_log("PORT from environment: " . ($_SERVER['PORT'] ?? $_ENV['PORT'] ?? getenv('PORT') ?: 'not set'));

// Use pcntl_exec if available, otherwise use shell_exec
if (function_exists('pcntl_exec')) {
    $parts = explode(' ', $command);
    $program = array_shift($parts);
    pcntl_exec($program, $parts);
} else {
    // Fallback: use shell to execute
    passthru($command);
}

