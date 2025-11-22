<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use GoldenPalms\CRM\Helpers\Helper;

class BookingController
{
    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $page = (int)($queryParams['page'] ?? 1);
        $perPage = (int)($queryParams['per_page'] ?? 20);
        $status = $queryParams['status'] ?? null;
        $dateFrom = $queryParams['date_from'] ?? null;
        $dateTo = $queryParams['date_to'] ?? null;

        $query = DB::table('bookings')
            ->leftJoin('guests', 'bookings.guest_id', '=', 'guests.id')
            ->leftJoin('units', 'bookings.unit_id', '=', 'units.id')
            ->select(
                'bookings.*',
                'guests.first_name as guest_first_name',
                'guests.last_name as guest_last_name',
                'guests.email as guest_email',
                'guests.phone as guest_phone',
                'units.unit_number'
            )
            ->orderBy('bookings.check_in', 'desc');

        if ($status) {
            $query->where('bookings.status', $status);
        }

        if ($dateFrom) {
            $query->where('bookings.check_in', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('bookings.check_out', '<=', $dateTo);
        }

        $total = $query->count();
        $bookings = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $response->getBody()->write(json_encode([
            'data' => $bookings,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage)
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function calendar(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $start = $queryParams['start'] ?? date('Y-m-01');
        $end = $queryParams['end'] ?? date('Y-m-t');

        $bookings = DB::table('bookings')
            ->leftJoin('guests', 'bookings.guest_id', '=', 'guests.id')
            ->leftJoin('units', 'bookings.unit_id', '=', 'units.id')
            ->select(
                'bookings.id',
                'bookings.booking_reference',
                'bookings.check_in',
                'bookings.check_out',
                'bookings.status',
                'bookings.number_of_guests',
                'guests.first_name',
                'guests.last_name',
                'units.unit_number',
                'units.unit_type'
            )
            ->whereBetween('bookings.check_in', [$start, $end])
            ->orWhereBetween('bookings.check_out', [$start, $end])
            ->get();

        $events = [];
        foreach ($bookings as $booking) {
            $events[] = [
                'id' => $booking->id,
                'title' => "{$booking->unit_number} - {$booking->first_name} {$booking->last_name}",
                'start' => $booking->check_in,
                'end' => date('Y-m-d', strtotime($booking->check_out . ' +1 day')),
                'backgroundColor' => $this->getStatusColor($booking->status),
                'status' => $booking->status,
                'extendedProps' => [
                    'reference' => $booking->booking_reference,
                    'guests' => $booking->number_of_guests,
                    'unit_type' => $booking->unit_type,
                    'status' => $booking->status
                ]
            ];
        }

        $response->getBody()->write(json_encode($events));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function calculatePrice(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $checkIn = $queryParams['check_in'] ?? null;
        $checkOut = $queryParams['check_out'] ?? null;
        $unitType = $queryParams['unit_type'] ?? null;

        if (!$checkIn || !$checkOut || !$unitType) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation error',
                'message' => 'check_in, check_out, and unit_type are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Calculate number of nights
        $checkInDate = new \DateTime($checkIn);
        $checkOutDate = new \DateTime($checkOut);
        $nights = $checkInDate->diff($checkOutDate)->days;

        if ($nights <= 0) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid dates',
                'message' => 'Check-out date must be after check-in date'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Get pricing rates for the date range
        $rates = DB::table('pricing_rates')
            ->where('unit_type', $unitType)
            ->where('is_active', 1)
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->whereBetween('start_date', [$checkIn, $checkOut])
                  ->orWhereBetween('end_date', [$checkIn, $checkOut])
                  ->orWhere(function ($q2) use ($checkIn, $checkOut) {
                      $q2->where('start_date', '<=', $checkIn)
                         ->where('end_date', '>=', $checkOut);
                  });
            })
            ->orderBy('rate_per_night', 'desc')
            ->get();

        $totalAmount = 0;
        $breakdown = [];
        $currentDate = clone $checkInDate;

        // Calculate price for each night
        for ($i = 0; $i < $nights; $i++) {
            $dateStr = $currentDate->format('Y-m-d');
            $rate = null;

            // Find the applicable rate for this date
            foreach ($rates as $r) {
                if ($dateStr >= $r->start_date && $dateStr <= $r->end_date) {
                    $rate = $r;
                    break;
                }
            }

            // If no specific rate found, use the default (lowest) rate
            if (!$rate) {
                $rate = DB::table('pricing_rates')
                    ->where('unit_type', $unitType)
                    ->where('is_active', 1)
                    ->orderBy('rate_per_night', 'asc')
                    ->first();
            }

            if ($rate) {
                $totalAmount += (float)$rate->rate_per_night;
                $breakdown[] = [
                    'date' => $dateStr,
                    'rate' => (float)$rate->rate_per_night,
                    'season' => $rate->season
                ];
            }

            $currentDate->modify('+1 day');
        }

        $response->getBody()->write(json_encode([
            'total_amount' => round($totalAmount, 2),
            'nights' => $nights,
            'breakdown' => $breakdown,
            'unit_type' => $unitType
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function checkAvailability(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $checkIn = $queryParams['check_in'] ?? null;
        $checkOut = $queryParams['check_out'] ?? null;
        $unitType = $queryParams['unit_type'] ?? null;

        if (!$checkIn || !$checkOut) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation error',
                'message' => 'check_in and check_out dates are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $query = DB::table('units')->where('is_active', 1);

        if ($unitType) {
            $query->where('unit_type', $unitType);
        }

        $units = $query->get();

        $availableUnits = [];
        foreach ($units as $unit) {
            // Check for conflicting bookings
            $conflict = DB::table('bookings')
                ->where('unit_id', $unit->id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($checkIn, $checkOut) {
                    $q->whereBetween('check_in', [$checkIn, $checkOut])
                      ->orWhereBetween('check_out', [$checkIn, $checkOut])
                      ->orWhere(function ($q2) use ($checkIn, $checkOut) {
                          $q2->where('check_in', '<=', $checkIn)
                             ->where('check_out', '>=', $checkOut);
                      });
                })
                ->exists();

            // Check for blocked dates
            $blocked = DB::table('unit_availability')
                ->where('unit_id', $unit->id)
                ->whereBetween('date', [$checkIn, $checkOut])
                ->exists();

            if (!$conflict && !$blocked) {
                $availableUnits[] = $unit;
            }
        }

        $response->getBody()->write(json_encode([
            'available' => count($availableUnits),
            'units' => $availableUnits
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function lookup(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $reference = $queryParams['reference'] ?? null;
        $email = $queryParams['email'] ?? null;

        if (!$reference || !$email) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation error',
                'message' => 'Booking reference and email are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $booking = DB::table('bookings')
            ->leftJoin('guests', 'bookings.guest_id', '=', 'guests.id')
            ->leftJoin('units', 'bookings.unit_id', '=', 'units.id')
            ->select(
                'bookings.*',
                'guests.first_name as guest_first_name',
                'guests.last_name as guest_last_name',
                'guests.email as guest_email',
                'guests.phone as guest_phone',
                'units.unit_number',
                'units.unit_type'
            )
            ->where('bookings.booking_reference', $reference)
            ->where('guests.email', $email)
            ->first();

        if (!$booking) {
            $response->getBody()->write(json_encode([
                'error' => 'Booking not found',
                'message' => 'No booking found with the provided reference and email. Please check your details and try again.'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($booking));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function confirm(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        $email = $data['email'] ?? null;

        if (!$email) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation error',
                'message' => 'Email is required for verification'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $booking = DB::table('bookings')
            ->leftJoin('guests', 'bookings.guest_id', '=', 'guests.id')
            ->where('bookings.id', $id)
            ->where('guests.email', $email)
            ->first();

        if (!$booking) {
            $response->getBody()->write(json_encode([
                'error' => 'Booking not found',
                'message' => 'No booking found or email does not match'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if ($booking->status === 'cancelled') {
            $response->getBody()->write(json_encode([
                'error' => 'Cannot confirm',
                'message' => 'This booking has been cancelled and cannot be confirmed'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Update booking status to confirmed
        DB::table('bookings')->where('id', $id)->update([
            'status' => 'confirmed',
            'updated_at' => Helper::now()
        ]);

        // Get updated booking with guest info for email
        $updatedBooking = DB::table('bookings')
            ->leftJoin('guests', 'bookings.guest_id', '=', 'guests.id')
            ->leftJoin('units', 'bookings.unit_id', '=', 'units.id')
            ->select('bookings.*', 'guests.*', 'units.unit_number')
            ->where('bookings.id', $id)
            ->first();

        // Send confirmation email
        try {
            if (class_exists('GoldenPalms\CRM\Services\EmailService')) {
                \GoldenPalms\CRM\Services\EmailService::sendBookingConfirmation($updatedBooking, $updatedBooking);
            }
        } catch (\Throwable $e) {
            error_log('Failed to send confirmation email: ' . $e->getMessage());
        }

        $response->getBody()->write(json_encode([
            'message' => 'Booking confirmed successfully',
            'booking' => $updatedBooking
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function cancelByCustomer(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        $email = $data['email'] ?? null;
        $reason = $data['reason'] ?? 'Cancelled by customer';

        if (!$email) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation error',
                'message' => 'Email is required for verification'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $booking = DB::table('bookings')
            ->leftJoin('guests', 'bookings.guest_id', '=', 'guests.id')
            ->where('bookings.id', $id)
            ->where('guests.email', $email)
            ->first();

        if (!$booking) {
            $response->getBody()->write(json_encode([
                'error' => 'Booking not found',
                'message' => 'No booking found or email does not match'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if ($booking->status === 'cancelled') {
            $response->getBody()->write(json_encode([
                'error' => 'Already cancelled',
                'message' => 'This booking has already been cancelled'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Update booking status to cancelled
        DB::table('bookings')->where('id', $id)->update([
            'status' => 'cancelled',
            'notes' => ($booking->notes ? $booking->notes . "\n\n" : '') . 'Cancelled by customer: ' . $reason . ' - ' . date('Y-m-d H:i:s'),
            'updated_at' => Helper::now()
        ]);

        // Log activity
        DB::table('activity_log')->insert([
            'user_id' => null, // Customer action
            'action' => 'cancel',
            'entity_type' => 'booking',
            'entity_id' => $id,
            'description' => "Booking cancelled by customer: {$reason}",
            'created_at' => Helper::now()
        ]);

        $updatedBooking = DB::table('bookings')
            ->leftJoin('guests', 'bookings.guest_id', '=', 'guests.id')
            ->leftJoin('units', 'bookings.unit_id', '=', 'units.id')
            ->select('bookings.*', 'guests.*', 'units.unit_number')
            ->where('bookings.id', $id)
            ->first();

        $response->getBody()->write(json_encode([
            'message' => 'Booking cancelled successfully',
            'booking' => $updatedBooking
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];

        $booking = DB::table('bookings')
            ->leftJoin('guests', 'bookings.guest_id', '=', 'guests.id')
            ->leftJoin('units', 'bookings.unit_id', '=', 'units.id')
            ->leftJoin('leads', 'bookings.lead_id', '=', 'leads.id')
            ->select(
                'bookings.*',
                'guests.*',
                'units.unit_number',
                'units.description as unit_description',
                'leads.source_id as lead_source_id'
            )
            ->where('bookings.id', $id)
            ->first();

        if (!$booking) {
            $response->getBody()->write(json_encode([
                'error' => 'Booking not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($booking));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        // Validate required fields
        $required = ['guest_id', 'unit_id', 'check_in', 'check_out', 'number_of_guests'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $response->getBody()->write(json_encode([
                    'error' => 'Validation error',
                    'message' => "Field '{$field}' is required"
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }

        // Check availability
        $conflict = DB::table('bookings')
            ->where('unit_id', $data['unit_id'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($data) {
                $q->whereBetween('check_in', [$data['check_in'], $data['check_out']])
                  ->orWhereBetween('check_out', [$data['check_in'], $data['check_out']]);
            })
            ->exists();

        if ($conflict) {
            $response->getBody()->write(json_encode([
                'error' => 'Booking conflict',
                'message' => 'Unit is not available for the selected dates'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $bookingReference = Helper::generateBookingReference();
        $totalAmount = $data['total_amount'] ?? 0;
        $depositAmount = $data['deposit_amount'] ?? 0;

        $bookingId = DB::table('bookings')->insertGetId([
            'booking_reference' => $bookingReference,
            'guest_id' => $data['guest_id'],
            'lead_id' => $data['lead_id'] ?? null,
            'unit_id' => $data['unit_id'],
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'number_of_guests' => $data['number_of_guests'],
            'unit_type' => $data['unit_type'] ?? '2_bedroom',
            'status' => $data['status'] ?? 'pending',
            'total_amount' => $totalAmount,
            'deposit_amount' => $depositAmount,
            'balance_amount' => $totalAmount - $depositAmount,
            'payment_status' => $depositAmount > 0 ? 'partial' : 'pending',
            'special_requests' => $data['special_requests'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
            'created_at' => Helper::now()
        ]);

        // Log activity
        DB::table('activity_log')->insert([
            'user_id' => $userId,
            'action' => 'create',
            'entity_type' => 'booking',
            'entity_id' => $bookingId,
            'description' => "Created booking {$bookingReference}",
            'created_at' => Helper::now()
        ]);

        $booking = $this->show($request->withAttribute('id', $bookingId), $response, ['id' => $bookingId]);
        return $booking;
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $booking = DB::table('bookings')->where('id', $id)->first();
        if (!$booking) {
            $response->getBody()->write(json_encode([
                'error' => 'Booking not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $updateData = [];
        $allowedFields = ['unit_id', 'check_in', 'check_out', 'number_of_guests', 'status',
                         'total_amount', 'deposit_amount', 'payment_status', 'special_requests', 'notes'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        // Recalculate balance if amounts changed
        if (isset($updateData['total_amount']) || isset($updateData['deposit_amount'])) {
            $totalAmount = $updateData['total_amount'] ?? $booking->total_amount;
            $depositAmount = $updateData['deposit_amount'] ?? $booking->deposit_amount;
            $updateData['balance_amount'] = $totalAmount - $depositAmount;
        }

        // Handle check-in/check-out
        if (isset($data['action'])) {
            if ($data['action'] === 'check_in') {
                $updateData['status'] = 'checked_in';
                $updateData['checked_in_at'] = Helper::now();
            } elseif ($data['action'] === 'check_out') {
                $updateData['status'] = 'checked_out';
                $updateData['checked_out_at'] = Helper::now();
            }
        }

        $updateData['updated_at'] = Helper::now();

        DB::table('bookings')->where('id', $id)->update($updateData);

        // Log activity
        DB::table('activity_log')->insert([
            'user_id' => $userId,
            'action' => 'update',
            'entity_type' => 'booking',
            'entity_id' => $id,
            'description' => "Updated booking #{$id}",
            'created_at' => Helper::now()
        ]);

        return $this->show($request, $response, $args);
    }

    public function cancel(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $booking = DB::table('bookings')->where('id', $id)->first();
        if (!$booking) {
            $response->getBody()->write(json_encode([
                'error' => 'Booking not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        DB::table('bookings')->where('id', $id)->update([
            'status' => 'cancelled',
            'cancellation_reason' => $data['reason'] ?? null,
            'cancelled_at' => Helper::now(),
            'updated_at' => Helper::now()
        ]);

        // Log activity
        DB::table('activity_log')->insert([
            'user_id' => $userId,
            'action' => 'cancel',
            'entity_type' => 'booking',
            'entity_id' => $id,
            'description' => "Cancelled booking #{$id}",
            'created_at' => Helper::now()
        ]);

        $response->getBody()->write(json_encode([
            'message' => 'Booking cancelled successfully'
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function confirmByLink(Request $request, Response $response, array $args): Response
    {
        $token = $args['token'] ?? null;
        
        if (!$token) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid confirmation link'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Decode token (simple base64 encoding of booking_id:email:hash)
        $decoded = base64_decode($token, true);
        if (!$decoded) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid confirmation link'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $parts = explode(':', $decoded);
        if (count($parts) !== 3) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid confirmation link'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $bookingId = (int)$parts[0];
        $email = $parts[1];
        $hash = $parts[2];
        
        // Verify hash
        $appSecret = $_ENV['APP_SECRET'] ?? 'goldenpalms_secret_key_2024';
        $expectedHash = substr(md5($bookingId . $email . $appSecret), 0, 8);
        if ($hash !== $expectedHash) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid confirmation link'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Get booking
        $booking = DB::table('bookings')
            ->leftJoin('guests', 'bookings.guest_id', '=', 'guests.id')
            ->where('bookings.id', $bookingId)
            ->where('guests.email', $email)
            ->first();
        
        if (!$booking) {
            $response->getBody()->write(json_encode([
                'error' => 'Booking not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        if ($booking->status === 'cancelled') {
            $response->getBody()->write(json_encode([
                'error' => 'Cannot confirm',
                'message' => 'This booking has been cancelled'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        if ($booking->status === 'confirmed') {
            // Already confirmed, show success page
            $guest = DB::table('guests')->where('id', $booking->guest_id)->first();
            $unit = DB::table('units')->where('id', $booking->unit_id)->first();
            $html = $this->getConfirmationSuccessPage($booking, $guest, $unit, true);
            $response->getBody()->write($html);
            return $response->withHeader('Content-Type', 'text/html');
        }
        
        // Update booking status to confirmed
        DB::table('bookings')->where('id', $bookingId)->update([
            'status' => 'confirmed',
            'updated_at' => Helper::now()
        ]);
        
        // Get updated booking with guest info for email
        $updatedBooking = DB::table('bookings')
            ->leftJoin('guests', 'bookings.guest_id', '=', 'guests.id')
            ->leftJoin('units', 'bookings.unit_id', '=', 'units.id')
            ->select('bookings.*', 'guests.*', 'units.unit_number')
            ->where('bookings.id', $bookingId)
            ->first();
        
        // Send confirmation email
        try {
            if (class_exists('GoldenPalms\CRM\Services\EmailService')) {
                \GoldenPalms\CRM\Services\EmailService::sendBookingConfirmation($updatedBooking, $updatedBooking);
            }
        } catch (\Throwable $e) {
            error_log('Failed to send confirmation email: ' . $e->getMessage());
        }
        
        // Return HTML success page
        $guest = DB::table('guests')->where('id', $booking->guest_id)->first();
        $unit = DB::table('units')->where('id', $booking->unit_id)->first();
        $html = $this->getConfirmationSuccessPage($updatedBooking, $guest, $unit, false);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
    
    private function getConfirmationSuccessPage($booking, $guest, $unit, $alreadyConfirmed = false): string
    {
        $checkIn = $booking->check_in ? date('F j, Y', strtotime($booking->check_in)) : 'TBA';
        $checkOut = $booking->check_out ? date('F j, Y', strtotime($booking->check_out)) : 'TBA';
        
        $message = $alreadyConfirmed 
            ? 'Your booking is already confirmed!' 
            : 'Your booking has been confirmed successfully!';
        
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - Golden Palms Beach Resort</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .container { background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); max-width: 600px; width: 100%; }
        h1 { color: #28a745; margin-bottom: 1rem; }
        .success-icon { font-size: 4rem; color: #28a745; margin-bottom: 1rem; }
        p { color: #666; line-height: 1.6; margin: 1rem 0; }
        .booking-info { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin: 2rem 0; text-align: left; }
        .booking-info p { margin: 0.75rem 0; }
        .booking-info strong { color: #667eea; }
        .btn { display: inline-block; background: #667eea; color: white; padding: 0.75rem 2rem; text-decoration: none; border-radius: 8px; margin-top: 1rem; font-weight: 600; }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>
        <h1>' . ($alreadyConfirmed ? 'Already Confirmed!' : 'Booking Confirmed!') . '</h1>
        <p>' . $message . '</p>
        <div class="booking-info">
            <p><strong>Booking Reference:</strong> ' . htmlspecialchars($booking->booking_reference ?? 'N/A') . '</p>
            <p><strong>Guest Name:</strong> ' . htmlspecialchars(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? '')) . '</p>
            <p><strong>Check-in:</strong> ' . htmlspecialchars($checkIn) . '</p>
            <p><strong>Check-out:</strong> ' . htmlspecialchars($checkOut) . '</p>
            <p><strong>Unit:</strong> ' . htmlspecialchars($unit->unit_number ?? 'TBA') . '</p>
            <p><strong>Total Amount:</strong> R' . number_format((float)($booking->total_amount ?? 0), 2) . '</p>
        </div>
        <p>A confirmation email has been sent to your email address with all the details.</p>
        <p>We look forward to welcoming you to Golden Palms Beach Resort!</p>
        <a href="/" class="btn">Return to Website</a>
    </div>
</body>
</html>';
    }

    private function getStatusColor(string $status): string
    {
        $colors = [
            'pending' => '#ffc107',
            'confirmed' => '#28a745',
            'checked_in' => '#17a2b8',
            'checked_out' => '#6c757d',
            'cancelled' => '#dc3545',
            'no_show' => '#6c757d'
        ];

        return $colors[$status] ?? '#6c757d';
    }
}

