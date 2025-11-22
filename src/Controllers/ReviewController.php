<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use GoldenPalms\CRM\Helpers\Helper;

class ReviewController
{
    public function sendRequest(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        if (empty($data['booking_id']) || empty($data['method'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation error',
                'message' => 'booking_id and method (email/whatsapp) are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $booking = DB::table('bookings')
            ->leftJoin('guests', 'bookings.guest_id', '=', 'guests.id')
            ->select('bookings.*', 'guests.first_name', 'guests.last_name', 'guests.email', 'guests.phone')
            ->where('bookings.id', $data['booking_id'])
            ->first();

        if (!$booking) {
            $response->getBody()->write(json_encode([
                'error' => 'Booking not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Generate review links
        $reviewLinks = [
            'google' => $_ENV['GOOGLE_REVIEW_URL'] ?? 'https://g.page/r/.../review',
            'tripadvisor' => $_ENV['TRIPADVISOR_REVIEW_URL'] ?? 'https://www.tripadvisor.com/...',
            'facebook' => $_ENV['FACEBOOK_REVIEW_URL'] ?? 'https://www.facebook.com/...'
        ];

        // Create review request record
        $requestId = DB::table('review_requests')->insertGetId([
            'booking_id' => $data['booking_id'],
            'guest_id' => $booking->guest_id,
            'method' => $data['method'],
            'status' => 'pending',
            'message' => $data['message'] ?? $this->getDefaultMessage($booking, $reviewLinks),
            'review_links' => json_encode($reviewLinks),
            'sent_by' => $userId,
            'created_at' => Helper::now()
        ]);

        // Send via email or WhatsApp
        if ($data['method'] === 'email' && $booking->email) {
            $this->sendEmailReviewRequest($booking, $reviewLinks, $data['message'] ?? null);
            DB::table('review_requests')->where('id', $requestId)->update([
                'status' => 'sent',
                'sent_at' => Helper::now()
            ]);
        } elseif ($data['method'] === 'whatsapp' && $booking->phone) {
            $this->sendWhatsAppReviewRequest($booking, $reviewLinks, $data['message'] ?? null);
            DB::table('review_requests')->where('id', $requestId)->update([
                'status' => 'sent',
                'sent_at' => Helper::now()
            ]);
        } else {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid contact method',
                'message' => 'Email or phone number not available for selected method'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Log communication
        DB::table('communications')->insert([
            'type' => $data['method'] === 'email' ? 'email' : 'whatsapp',
            'direction' => 'outbound',
            'guest_id' => $booking->guest_id,
            'booking_id' => $data['booking_id'],
            'subject' => 'Review Request',
            'message' => $this->getDefaultMessage($booking, $reviewLinks),
            'to_email' => $data['method'] === 'email' ? $booking->email : null,
            'to_phone' => $data['method'] === 'whatsapp' ? $booking->phone : null,
            'status' => 'sent',
            'sent_by' => $userId,
            'sent_at' => Helper::now(),
            'created_at' => Helper::now()
        ]);

        $response->getBody()->write(json_encode([
            'message' => 'Review request sent successfully',
            'request_id' => $requestId
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $page = (int)($queryParams['page'] ?? 1);
        $perPage = (int)($queryParams['per_page'] ?? 20);

        $reviews = DB::table('reviews')
            ->leftJoin('guests', 'reviews.guest_id', '=', 'guests.id')
            ->leftJoin('bookings', 'reviews.booking_id', '=', 'bookings.id')
            ->select(
                'reviews.*',
                'guests.first_name',
                'guests.last_name',
                'bookings.booking_reference'
            )
            ->orderBy('reviews.reviewed_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $total = DB::table('reviews')->count();

        $response->getBody()->write(json_encode([
            'data' => $reviews,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage)
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function analytics(Request $request, Response $response): Response
    {
        $totalReviews = DB::table('reviews')->count();
        $averageRating = DB::table('reviews')->avg('rating');
        $totalRequests = DB::table('review_requests')->count();
        $sentRequests = DB::table('review_requests')->where('status', 'sent')->count();
        $reviewedCount = DB::table('review_requests')->where('status', 'reviewed')->count();

        $platforms = DB::table('reviews')
            ->select('platform', DB::raw('count(*) as count'))
            ->groupBy('platform')
            ->get();

        $ratings = DB::table('reviews')
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->get();

        $analytics = [
            'total_reviews' => $totalReviews,
            'average_rating' => round((float)$averageRating, 2),
            'total_requests' => $totalRequests,
            'sent_requests' => $sentRequests,
            'reviewed_count' => $reviewedCount,
            'conversion_rate' => $sentRequests > 0 
                ? round(($reviewedCount / $sentRequests) * 100, 2) 
                : 0,
            'by_platform' => $platforms,
            'by_rating' => $ratings
        ];

        $response->getBody()->write(json_encode($analytics));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getDefaultMessage($booking, $reviewLinks): string
    {
        $guestName = $booking->first_name;
        $message = "Hi {$guestName},\n\n";
        $message .= "Thank you for staying with us at Golden Palms Beach Resort!\n\n";
        $message .= "We hope you had a wonderful time. We would love to hear about your experience.\n\n";
        $message .= "Please leave us a review:\n";
        $message .= "Google: {$reviewLinks['google']}\n";
        $message .= "TripAdvisor: {$reviewLinks['tripadvisor']}\n";
        $message .= "Facebook: {$reviewLinks['facebook']}\n\n";
        $message .= "Thank you for your support!\n\n";
        $message .= "Golden Palms Beach Resort Team";

        return $message;
    }

    private function sendEmailReviewRequest($booking, $reviewLinks, $customMessage = null): void
    {
        // TODO: Implement email sending using PHPMailer or email service
        // This is a placeholder - implement actual email sending
    }

    private function sendWhatsAppReviewRequest($booking, $reviewLinks, $customMessage = null): void
    {
        // TODO: Implement WhatsApp sending using WhatsApp Business API
        // This is a placeholder - implement actual WhatsApp sending
    }
}

