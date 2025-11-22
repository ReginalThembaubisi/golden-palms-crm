<?php
/**
 * Simple Static File Server
 * Use this for faster static file serving during development
 * Run: php -S localhost:8000 server-simple.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);

// Skip API routes - let main index.php handle them
if (strpos($uri, '/api/') === 0 || $uri === '/api') {
    // Forward to main index.php for API
    require __DIR__ . '/index.php';
    exit;
}

// Determine file path
$publicDir = __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;

// Handle admin routes
if (strpos($uri, '/admin') === 0) {
    // Admin dashboard
    if ($uri === '/admin' || $uri === '/admin/') {
        $filePath = $publicDir . 'admin' . DIRECTORY_SEPARATOR . 'index.html';
    } else {
        // Admin assets (CSS, JS, etc.)
        $path = ltrim($uri, '/');
        $path = str_replace(['..', '\\'], ['', '/'], $path);
        $filePath = $publicDir . $path;
    }
} elseif ($uri === '/' || $uri === '') {
    // Customer website homepage
    $filePath = $publicDir . 'index.html';
} else {
    // Other public files
    $path = ltrim($uri, '/');
    $path = str_replace(['..', '\\'], ['', '/'], $path);
    $filePath = $publicDir . $path;
}

// Security check
$realPath = realpath($filePath);
$realPublicDir = realpath($publicDir);

if ($realPath && $realPublicDir && strpos($realPath, $realPublicDir) === 0 && is_file($realPath)) {
    $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
    
    $contentTypes = [
        'html' => 'text/html; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'pdf' => 'application/pdf',
    ];
    
    $contentType = $contentTypes[$extension] ?? 'text/plain';
    
    // Set headers
    header('Content-Type: ' . $contentType);
    
    // Cache headers for static assets
    if (in_array($extension, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg'])) {
        header('Cache-Control: public, max-age=3600');
    }
    
    // Output file
    readfile($realPath);
    exit;
}

// Fallback to index.html
$indexPath = $publicDir . 'index.html';
if (file_exists($indexPath)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($indexPath);
    exit;
}

// 404
http_response_code(404);
echo 'File not found';

