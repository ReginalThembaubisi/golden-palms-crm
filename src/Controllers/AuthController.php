<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController
{
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation error',
                'message' => 'Username and password are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $user = DB::table('users')
            ->where('username', $username)
            ->where('is_active', 1)
            ->first();

        if (!$user || !password_verify($password, $user->password)) {
            $response->getBody()->write(json_encode([
                'error' => 'Authentication failed',
                'message' => 'Invalid username or password'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Update last login
        DB::table('users')->where('id', $user->id)->update(['last_login' => date('Y-m-d H:i:s')]);

        // Generate JWT token
        $payload = [
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'exp' => time() + ($_ENV['JWT_EXPIRY'] ?? 86400)
        ];

        $token = JWT::encode($payload, $_ENV['JWT_SECRET'] ?? 'secret', 'HS256');

        $response->getBody()->write(json_encode([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function logout(Request $request, Response $response): Response
    {
        // In a stateless JWT system, logout is handled client-side by removing the token
        // Optionally, you could implement a token blacklist here
        $response->getBody()->write(json_encode([
            'message' => 'Logged out successfully'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function me(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');

        $user = DB::table('users')
            ->where('id', $userId)
            ->first();

        if (!$user) {
            $response->getBody()->write(json_encode([
                'error' => 'User not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'role' => $user->role
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}

