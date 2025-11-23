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
        try {
            // Try to initialize database
            Database::initialize();
            
            // Auto-initialize database schema on first API call (Railway deployment)
            // Only runs once if tables don't exist
            static $dbInitialized = false;
            if (!$dbInitialized && ($_ENV['AUTO_INIT_DB'] ?? 'true') === 'true') {
                try {
                    // After Database::initialize() calls setAsGlobal(), use static methods
                    // Test connection first
                    \Illuminate\Database\Capsule\Manager::connection()->getPdo();
                    
                    if (!\Illuminate\Database\Capsule\Manager::schema()->hasTable('users')) {
                        // Database not initialized, run init script
                        $initScript = __DIR__ . '/database/init.php';
                        if (file_exists($initScript)) {
                            // Run init script (non-blocking)
                            $output = [];
                            $returnVar = 0;
                            @exec("php $initScript 2>&1", $output, $returnVar);
                            if ($returnVar === 0) {
                                error_log('Database auto-initialized successfully');
                            } else {
                                error_log('Database init script returned: ' . implode("\n", $output));
                            }
                        }
                    }
                    $dbInitialized = true;
                } catch (\PDOException $e) {
                    // Database connection failed - log but don't crash
                    error_log('Database connection failed: ' . $e->getMessage());
                    // Return a helpful error response instead of crashing
                    if ($uri === '/api') {
                        $response = new \Slim\Psr7\Response();
                        $response->getBody()->write(json_encode([
                            'message' => 'Database connection failed',
                            'error' => 'Please check Railway deploy logs and ensure MySQL service is running',
                            'hint' => 'Make sure MYSQL_URL environment variable is set'
                        ]));
                        return $response->withStatus(503)->withHeader('Content-Type', 'application/json');
                    }
                } catch (\Exception $e) {
                    // Other errors - log but don't crash
                    error_log('Database auto-init error: ' . $e->getMessage());
                }
            }
        } catch (\PDOException $e) {
            // Database connection failed - return error instead of crashing
            error_log('Database initialization failed: ' . $e->getMessage());
            if ($uri === '/api') {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode([
                    'message' => 'Database connection failed',
                    'error' => $e->getMessage(),
                    'hint' => 'Check Railway deploy logs. Ensure MySQL service is added and MYSQL_URL is set.'
                ]));
                return $response->withStatus(503)->withHeader('Content-Type', 'application/json');
            }
        } catch (\Exception $e) {
            // Log but don't fail - database might not be ready yet
            error_log('Database initialization warning: ' . $e->getMessage());
        }
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

// API info route - also triggers database initialization
$app->get('/api', function (Request $request, Response $response) {
    // Try to initialize database and create tables if needed
    try {
        Database::initialize();
        
        // Check if tables exist, if not, initialize directly
        // Use static methods after Database::initialize() calls setAsGlobal()
        $tablesExist = \Illuminate\Database\Capsule\Manager::schema()->hasTable('users');
        $initStatus = 'unknown';
        
        try {
            $tablesExist = $capsule->schema()->hasTable('users');
        } catch (\Exception $e) {
            // Connection might have failed
            error_log('Database check failed: ' . $e->getMessage());
            throw $e;
        }
        
        if (!$tablesExist) {
            // Initialize database directly (don't use exec - more reliable)
            $initStatus = 'initializing';
            error_log('Tables not found, initializing database...');
            
            try {
                // Read and execute schema.sql directly
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
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (empty($statement)) continue;
                    
                    try {
                        $pdo->exec($statement);
                        $executed++;
                    } catch (\PDOException $e) {
                        // Ignore "table already exists" errors
                        if (strpos($e->getMessage(), 'already exists') === false && 
                            strpos($e->getMessage(), 'Duplicate') === false) {
                            error_log('SQL Error: ' . substr($e->getMessage(), 0, 200));
                        }
                    }
                }
                
                error_log("Database initialization: $executed statements executed");
                
                // Verify tables were created
                $tablesExist = $capsule->schema()->hasTable('users');
                $initStatus = $tablesExist ? 'initialized' : 'failed';
                
            } catch (\Exception $e) {
                error_log('Database initialization error: ' . $e->getMessage());
                $initStatus = 'error: ' . $e->getMessage();
            }
        } else {
            $initStatus = 'already_initialized';
        }
        
        $response->getBody()->write(json_encode([
            'message' => 'Golden Palms CRM API',
            'version' => '1.0.0',
            'status' => 'running',
            'database' => [
                'tables_exist' => $tablesExist,
                'initialization' => $initStatus
            ],
            'endpoints' => [
                'POST /api/auth/login' => 'User login',
                'GET /api/leads' => 'List leads',
                'POST /api/leads/website' => 'Submit website lead',
                'GET /api/bookings' => 'List bookings',
                'GET /api/bookings/availability' => 'Check availability'
            ]
        ]));
    } catch (\PDOException $e) {
        // Database connection failed
        error_log('Database connection error: ' . $e->getMessage());
        $response->getBody()->write(json_encode([
            'message' => 'Golden Palms CRM API',
            'version' => '1.0.0',
            'status' => 'database_connection_failed',
            'error' => $e->getMessage(),
            'hint' => 'Check Railway deploy logs. Ensure MySQL service is running and MYSQL_URL is set.'
        ]));
        return $response->withStatus(503)->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        // Other errors
        error_log('API endpoint error: ' . $e->getMessage());
        $response->getBody()->write(json_encode([
            'message' => 'Golden Palms CRM API',
            'version' => '1.0.0',
            'status' => 'error',
            'error' => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
    
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

