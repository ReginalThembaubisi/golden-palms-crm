<?php

declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use GoldenPalms\CRM\Controllers\AuthController;
use GoldenPalms\CRM\Controllers\LeadController;
use GoldenPalms\CRM\Controllers\BookingController;
use GoldenPalms\CRM\Controllers\GuestController;
use GoldenPalms\CRM\Controllers\CampaignController;
use GoldenPalms\CRM\Controllers\ReviewController;
use GoldenPalms\CRM\Controllers\WebsiteController;
use GoldenPalms\CRM\Middleware\AuthMiddleware;

return function (App $app) {
    // Handle OPTIONS requests for CORS preflight
    $app->options('/api/{routes:.+}', function (Request $request, Response $response) {
        return $response;
    });
    
    // Public routes
    $app->post('/api/auth/login', [AuthController::class, 'login']);
    $app->post('/api/webhooks/meta-leads', [LeadController::class, 'handleMetaLead']);
    $app->post('/api/leads/website', [LeadController::class, 'handleWebsiteLead']);
    $app->get('/api/website/content', [WebsiteController::class, 'getContent']); // Public access to published content
    $app->get('/api/bookings/confirm/{token}', [BookingController::class, 'confirmByLink']); // Public - one-click email confirmation

    // Protected routes (require authentication)
    $app->group('/api', function ($group) {
        // Auth
        $group->post('/auth/logout', [AuthController::class, 'logout']);
        $group->get('/auth/me', [AuthController::class, 'me']);

        // Leads
        $group->get('/leads', [LeadController::class, 'index']);
        $group->get('/leads/{id}', [LeadController::class, 'show']);
        $group->post('/leads', [LeadController::class, 'create']);
        $group->put('/leads/{id}', [LeadController::class, 'update']);
        $group->delete('/leads/{id}', [LeadController::class, 'delete']);
        $group->post('/leads/{id}/cancel', [LeadController::class, 'cancel']);
        $group->post('/leads/{id}/convert', [LeadController::class, 'convertToBooking']);

        // Bookings
        $group->get('/bookings', [BookingController::class, 'index']);
        $group->get('/bookings/calculate-price', [BookingController::class, 'calculatePrice']);
        $group->get('/bookings/calendar', [BookingController::class, 'calendar']);
        $group->get('/bookings/availability', [BookingController::class, 'checkAvailability']);
        $group->get('/bookings/{id}', [BookingController::class, 'show']);
        $group->post('/bookings', [BookingController::class, 'create']);
        $group->put('/bookings/{id}', [BookingController::class, 'update']);
        $group->delete('/bookings/{id}', [BookingController::class, 'cancel']);

        // Guests
        $group->get('/guests', [GuestController::class, 'index']);
        $group->get('/guests/{id}', [GuestController::class, 'show']);
        $group->post('/guests', [GuestController::class, 'create']);
        $group->put('/guests/{id}', [GuestController::class, 'update']);
        $group->get('/guests/{id}/bookings', [GuestController::class, 'getBookings']);
        $group->get('/guests/{id}/communications', [GuestController::class, 'getCommunications']);

        // Campaigns
        $group->get('/campaigns', [CampaignController::class, 'index']);
        $group->get('/campaigns/{id}', [CampaignController::class, 'show']);
        $group->post('/campaigns', [CampaignController::class, 'create']);
        $group->put('/campaigns/{id}', [CampaignController::class, 'update']);
        $group->post('/campaigns/{id}/send', [CampaignController::class, 'send']);
        $group->get('/campaigns/{id}/analytics', [CampaignController::class, 'analytics']);

        // Reviews
        $group->post('/reviews/request', [ReviewController::class, 'sendRequest']);
        $group->get('/reviews', [ReviewController::class, 'index']);
        $group->get('/reviews/analytics', [ReviewController::class, 'analytics']);

        // Website (admin only - create/update/delete)
        $group->post('/website/content', [WebsiteController::class, 'createContent']);
        $group->put('/website/content/{id}', [WebsiteController::class, 'updateContent']);
        $group->post('/website/media', [WebsiteController::class, 'uploadMedia']);
        // Note: GET /api/website/content is public (defined above)

        // Dashboard
        $group->get('/dashboard/stats', function (Request $request, Response $response) {
            // TODO: Implement dashboard stats
            $response->getBody()->write(json_encode([
                'leads_today' => 0,
                'bookings_today' => 0,
                'occupancy_rate' => 0,
                'revenue_month' => 0
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        });
    })->add(new AuthMiddleware());
};

