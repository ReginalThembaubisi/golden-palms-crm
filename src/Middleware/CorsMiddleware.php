<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // Get origin from request or use APP_URL
        $origin = $request->getHeaderLine('Origin');
        $allowedOrigin = $_ENV['APP_URL'] ?? '*';
        
        // If origin matches APP_URL or is from same domain, allow it
        // Otherwise use wildcard for development
        if ($origin && $allowedOrigin !== '*') {
            $appUrlHost = parse_url($allowedOrigin, PHP_URL_HOST);
            $originHost = parse_url($origin, PHP_URL_HOST);
            if ($appUrlHost && $originHost && $appUrlHost === $originHost) {
                $allowedOrigin = $origin;
            }
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }
}

