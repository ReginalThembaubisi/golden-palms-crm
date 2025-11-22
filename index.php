<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;
use GoldenPalms\CRM\Middleware\CorsMiddleware;
use GoldenPalms\CRM\Middleware\AuthMiddleware;
use GoldenPalms\CRM\Config\Database;
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Create Slim app
$app = AppFactory::create();

// Initialize database only for API routes (lazy loading)
$app->add(function (Request $request, $handler) {
    $uri = $request->getUri()->getPath();
    // Only initialize DB for API routes
    if (strpos($uri, '/api/') === 0 || $uri === '/api') {
        Database::initialize();
    }
    return $handler->handle($request);
});

// Add error middleware (disable in production for better performance)
// In production (Railway), disable error details for security
$isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
$displayErrorDetails = !$isProduction && ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);

// Add CORS middleware
$app->add(new CorsMiddleware());

// Parse JSON body
$app->addBodyParsingMiddleware();

// API Routes - MUST be registered BEFORE catch-all route
$routes = require __DIR__ . '/routes/api.php';
$routes($app);

// API info route
$app->get('/api', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'message' => 'Golden Palms CRM API',
        'version' => '1.0.0',
        'status' => 'running',
        'endpoints' => [
            'POST /api/auth/login' => 'User login',
            'GET /api/leads' => 'List leads',
            'POST /api/leads/website' => 'Submit website lead',
            'GET /api/bookings' => 'List bookings',
            'GET /api/bookings/availability' => 'Check availability'
        ]
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Serve static files - register specific routes first for better performance
$app->get('/css/{file}', function (Request $request, Response $response, array $args) {
    $file = $args['file'];
    $filePath = __DIR__ . '/public/css/' . basename($file);
    
    if (file_exists($filePath) && is_file($filePath)) {
        $content = file_get_contents($filePath);
        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', 'text/css; charset=utf-8')
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }
    
    return $response->withStatus(404);
});

$app->get('/js/{file}', function (Request $request, Response $response, array $args) {
    $file = $args['file'];
    $filePath = __DIR__ . '/public/js/' . basename($file);
    
    if (file_exists($filePath) && is_file($filePath)) {
        $content = file_get_contents($filePath);
        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }
    
    return $response->withStatus(404);
});

$app->get('/images/{file}', function (Request $request, Response $response, array $args) {
    $file = $args['file'];
    $filePath = __DIR__ . '/public/images/' . basename($file);
    
    if (file_exists($filePath) && is_file($filePath)) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $contentTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'webp' => 'image/webp'
        ];
        
        $contentType = $contentTypes[$extension] ?? 'image/jpeg';
        $content = file_get_contents($filePath);
        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }
    
    return $response->withStatus(404);
});

// Admin dashboard route (must be before catch-all)
$app->get('/admin[/{path:.*}]', function (Request $request, Response $response, array $args) {
    $path = $args['path'] ?? '';
    $uri = $request->getUri()->getPath();
    $publicDir = __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
    
    // If it's /admin or /admin/, serve admin/index.html
    if (empty($path) || $path === '/') {
        $filePath = $publicDir . 'admin' . DIRECTORY_SEPARATOR . 'index.html';
    } else {
        // Admin assets (CSS, JS, etc. under /admin/)
        $path = str_replace(['..', '\\'], ['', '/'], $path);
        $path = ltrim($path, '/');
        $filePath = $publicDir . 'admin' . DIRECTORY_SEPARATOR . $path;
    }
    
    // Check if file exists
    $realPath = realpath($filePath);
    $realPublicDir = realpath($publicDir);
    
    if ($realPath && $realPublicDir && strpos($realPath, $realPublicDir) === 0 && is_file($realPath)) {
        $content = @file_get_contents($realPath);
        if ($content === false) {
            $response->getBody()->write('Error reading file');
            return $response->withStatus(500);
        }
        
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
        ];
        
        $contentType = $contentTypes[$extension] ?? 'text/plain';
        
        if (in_array($extension, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg'])) {
            $response = $response->withHeader('Cache-Control', 'public, max-age=3600');
        }
        
        $response->getBody()->write($content);
        return $response->withHeader('Content-Type', $contentType);
    }
    
    // Fallback to admin/index.html
    $adminIndex = $publicDir . 'admin' . DIRECTORY_SEPARATOR . 'index.html';
    if (file_exists($adminIndex)) {
        $content = file_get_contents($adminIndex);
        $response->getBody()->write($content);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
    
    return $response->withStatus(404)->withHeader('Content-Type', 'text/plain');
});

// Serve static files from public directory (catch-all - must be LAST)
$app->get('/{path:.*}', function (Request $request, Response $response, array $args) {
    $path = $args['path'] ?? '';
    $uri = $request->getUri()->getPath();
    
    // Skip API routes
    if (strpos($uri, '/api/') === 0 || $uri === '/api') {
        $response->getBody()->write(json_encode(['error' => 'API endpoint not found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
    
    // Skip admin routes (handled above)
    if (strpos($uri, '/admin') === 0) {
        return $response->withStatus(404)->withHeader('Content-Type', 'text/plain');
    }
    
    // Skip already handled routes
    if (strpos($uri, '/css/') === 0 || strpos($uri, '/js/') === 0 || strpos($uri, '/images/') === 0) {
        return $response->withStatus(404);
    }
    
    // Determine file path
    $publicDir = __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
    
    if (empty($path) || $path === '/') {
        $filePath = $publicDir . 'index.html';
    } else {
        // Clean path to prevent directory traversal
        $path = str_replace(['..', '\\'], ['', '/'], $path);
        $path = ltrim($path, '/');
        $filePath = $publicDir . $path;
    }
    
    // Normalize path and check if it's within public directory
    $realPath = realpath($filePath);
    $realPublicDir = realpath($publicDir);
    
    // Quick check: file exists and is within public directory
    if ($realPath && $realPublicDir && strpos($realPath, $realPublicDir) === 0 && is_file($realPath)) {
        $content = @file_get_contents($realPath);
        if ($content === false) {
            $response->getBody()->write('Error reading file');
            return $response->withStatus(500);
        }
        
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
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf'
        ];
        
        $contentType = $contentTypes[$extension] ?? 'text/plain';
        
        // Add cache headers for static assets
        if (in_array($extension, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'woff', 'woff2'])) {
            $response = $response->withHeader('Cache-Control', 'public, max-age=3600');
        }
        
        $response->getBody()->write($content);
        return $response->withHeader('Content-Type', $contentType);
    }
    
    // Fallback: serve index.html for root or 404
    if (empty($path) || $path === '/') {
        $indexPath = $publicDir . 'index.html';
        if (file_exists($indexPath)) {
            $content = file_get_contents($indexPath);
            $response->getBody()->write($content);
            return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        }
    }
    
    // Return 404
    $response->getBody()->write('File not found');
    return $response->withStatus(404)->withHeader('Content-Type', 'text/plain');
});

$app->run();

