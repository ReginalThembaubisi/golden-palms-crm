<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use GoldenPalms\CRM\Helpers\Helper;

class GuestController
{
    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $page = (int)($queryParams['page'] ?? 1);
        $perPage = (int)($queryParams['per_page'] ?? 20);
        $search = $queryParams['search'] ?? null;

        $query = DB::table('guests')->orderBy('last_name', 'asc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $guests = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $response->getBody()->write(json_encode([
            'data' => $guests,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage)
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];

        $guest = DB::table('guests')->where('id', $id)->first();

        if (!$guest) {
            $response->getBody()->write(json_encode([
                'error' => 'Guest not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Decode JSON fields
        if ($guest->special_occasions) {
            $guest->special_occasions = json_decode($guest->special_occasions, true);
        }
        if ($guest->preferences) {
            $guest->preferences = json_decode($guest->preferences, true);
        }
        if ($guest->tags) {
            $guest->tags = json_decode($guest->tags, true);
        }

        $response->getBody()->write(json_encode($guest));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (empty($data['first_name']) || empty($data['last_name'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation error',
                'message' => 'First name and last name are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $guestId = DB::table('guests')->insertGetId([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'phone_alt' => $data['phone_alt'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? 'South Africa',
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'preferred_contact' => $data['preferred_contact'] ?? 'email',
            'dietary_restrictions' => $data['dietary_restrictions'] ?? null,
            'accessibility_needs' => $data['accessibility_needs'] ?? null,
            'special_occasions' => isset($data['special_occasions']) ? json_encode($data['special_occasions']) : null,
            'preferences' => isset($data['preferences']) ? json_encode($data['preferences']) : null,
            'tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
            'created_at' => Helper::now()
        ]);

        $guest = DB::table('guests')->where('id', $guestId)->first();
        $response->getBody()->write(json_encode($guest));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();

        $guest = DB::table('guests')->where('id', $id)->first();
        if (!$guest) {
            $response->getBody()->write(json_encode([
                'error' => 'Guest not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $updateData = [];
        $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'phone_alt', 'address', 'city',
                         'country', 'date_of_birth', 'preferred_contact', 'dietary_restrictions',
                         'accessibility_needs', 'special_occasions', 'preferences', 'tags', 'notes'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if (in_array($field, ['special_occasions', 'preferences', 'tags']) && is_array($data[$field])) {
                    $updateData[$field] = json_encode($data[$field]);
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }

        $updateData['updated_at'] = Helper::now();

        DB::table('guests')->where('id', $id)->update($updateData);

        return $this->show($request, $response, $args);
    }

    public function getBookings(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];

        $bookings = DB::table('bookings')
            ->leftJoin('units', 'bookings.unit_id', '=', 'units.id')
            ->select(
                'bookings.*',
                'units.unit_number',
                'units.unit_type'
            )
            ->where('bookings.guest_id', $id)
            ->orderBy('bookings.check_in', 'desc')
            ->get();

        $response->getBody()->write(json_encode($bookings));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getCommunications(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];

        $communications = DB::table('communications')
            ->where('guest_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $response->getBody()->write(json_encode($communications));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

