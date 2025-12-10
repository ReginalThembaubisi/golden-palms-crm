<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use GoldenPalms\CRM\Helpers\Helper;
use GoldenPalms\CRM\Services\EmailService;

class LeadController
{
    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $page = (int)($queryParams['page'] ?? 1);
        $perPage = (int)($queryParams['per_page'] ?? 20);
        $status = $queryParams['status'] ?? null;
        $sourceId = $queryParams['source_id'] ?? null;
        $search = $queryParams['search'] ?? null;

        $query = DB::table('leads')
            ->leftJoin('lead_sources', 'leads.source_id', '=', 'lead_sources.id')
            ->leftJoin('users', 'leads.assigned_to', '=', 'users.id')
            ->select(
                'leads.*',
                'lead_sources.name as source_name',
                'lead_sources.type as source_type',
                'lead_sources.color as source_color',
                'users.first_name as assigned_first_name',
                'users.last_name as assigned_last_name'
            )
            ->orderBy('leads.created_at', 'desc');

        if ($status) {
            $query->where('leads.status', $status);
        }

        if ($sourceId) {
            $query->where('leads.source_id', $sourceId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('leads.first_name', 'like', "%{$search}%")
                  ->orWhere('leads.last_name', 'like', "%{$search}%")
                  ->orWhere('leads.email', 'like', "%{$search}%")
                  ->orWhere('leads.phone', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $leads = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $response->getBody()->write(json_encode([
            'data' => $leads,
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

        $lead = DB::table('leads')
            ->leftJoin('lead_sources', 'leads.source_id', '=', 'lead_sources.id')
            ->leftJoin('users', 'leads.assigned_to', '=', 'users.id')
            ->leftJoin('bookings', 'leads.converted_to_booking_id', '=', 'bookings.id')
            ->select(
                'leads.*',
                'lead_sources.name as source_name',
                'lead_sources.type as source_type',
                'lead_sources.color as source_color',
                'users.first_name as assigned_first_name',
                'users.last_name as assigned_last_name',
                'bookings.booking_reference',
                'bookings.status as booking_status'
            )
            ->where('leads.id', $id)
            ->first();

        if (!$lead) {
            $response->getBody()->write(json_encode([
                'error' => 'Lead not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Get communication history
        $communications = DB::table('communications')
            ->where('lead_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $lead->communications = $communications;

        $response->getBody()->write(json_encode($lead));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        // Validate required fields
        $required = ['first_name', 'last_name', 'source_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $response->getBody()->write(json_encode([
                    'error' => 'Validation error',
                    'message' => "Field '{$field}' is required"
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }

        $leadId = DB::table('leads')->insertGetId([
            'source_id' => (int)$data['source_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'] ?? 'new',
            'priority' => $data['priority'] ?? 'medium',
            'assigned_to' => $data['assigned_to'] ?? null,
            'message' => $data['message'] ?? null,
            'notes' => $data['notes'] ?? null,
            'tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
            'created_at' => Helper::now(),
            'updated_at' => Helper::now()
        ]);

        // Auto-score the lead
        \GoldenPalms\CRM\Services\LeadScoringService::updateLeadScore($leadId);

        // Trigger workflows
        \GoldenPalms\CRM\Services\WorkflowEngine::processTrigger(
            'lead_created',
            'lead',
            $leadId,
            ['lead' => (object)$data]
        );

        // Log activity
        DB::table('activity_log')->insert([
            'user_id' => $userId,
            'action' => 'create',
            'entity_type' => 'lead',
            'entity_id' => $leadId,
            'description' => "Created new lead: {$data['first_name']} {$data['last_name']}",
            'created_at' => Helper::now()
        ]);

        $lead = DB::table('leads')
            ->leftJoin('lead_sources', 'leads.source_id', '=', 'lead_sources.id')
            ->select('leads.*', 'lead_sources.name as source_name')
            ->where('leads.id', $leadId)
            ->first();

        $response->getBody()->write(json_encode($lead));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $lead = DB::table('leads')->where('id', $id)->first();
        if (!$lead) {
            $response->getBody()->write(json_encode([
                'error' => 'Lead not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $updateData = [];
        $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'status', 'priority', 
                         'assigned_to', 'message', 'notes', 'tags', 'contacted_at'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'tags' && is_array($data[$field])) {
                    $updateData[$field] = json_encode($data[$field]);
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }

        $updateData['updated_at'] = Helper::now();

        DB::table('leads')->where('id', $id)->update($updateData);

        // Recalculate lead score if relevant fields changed
        $scoreFields = ['status', 'priority', 'email', 'phone', 'contacted_at'];
        if (array_intersect_key($updateData, array_flip($scoreFields))) {
            \GoldenPalms\CRM\Services\LeadScoringService::updateLeadScore($id);
        }

        // Trigger workflow if status changed
        if (isset($updateData['status'])) {
            \GoldenPalms\CRM\Services\WorkflowEngine::processTrigger(
                'lead_status_changed',
                'lead',
                $id,
                ['old_status' => $lead->status, 'new_status' => $updateData['status']]
            );
        }

        // Log activity
        DB::table('activity_log')->insert([
            'user_id' => $userId,
            'action' => 'update',
            'entity_type' => 'lead',
            'entity_id' => $id,
            'description' => "Updated lead #{$id}",
            'created_at' => Helper::now()
        ]);

        $updatedLead = DB::table('leads')
            ->leftJoin('lead_sources', 'leads.source_id', '=', 'lead_sources.id')
            ->select('leads.*', 'lead_sources.name as source_name')
            ->where('leads.id', $id)
            ->first();

        $response->getBody()->write(json_encode($updatedLead));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $userId = $request->getAttribute('user_id');

        $lead = DB::table('leads')->where('id', $id)->first();
        if (!$lead) {
            $response->getBody()->write(json_encode([
                'error' => 'Lead not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Check if lead is converted to booking
        if ($lead->converted_to_booking_id) {
            $response->getBody()->write(json_encode([
                'error' => 'Cannot delete lead that has been converted to a booking',
                'message' => 'This lead has been converted to a booking and cannot be deleted. You can mark it as "lost" instead.'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Delete the lead
        DB::table('leads')->where('id', $id)->delete();

        // Log activity
        DB::table('activity_log')->insert([
            'user_id' => $userId,
            'action' => 'delete',
            'entity_type' => 'lead',
            'entity_id' => $id,
            'description' => "Deleted lead: {$lead->first_name} {$lead->last_name}",
            'created_at' => Helper::now()
        ]);

        $response->getBody()->write(json_encode([
            'message' => 'Lead deleted successfully'
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function cancel(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $lead = DB::table('leads')->where('id', $id)->first();
        if (!$lead) {
            $response->getBody()->write(json_encode([
                'error' => 'Lead not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Update lead status to 'lost' (cancelled)
        DB::table('leads')->where('id', $id)->update([
            'status' => 'lost',
            'notes' => ($lead->notes ? $lead->notes . "\n\n" : '') . 'Cancelled: ' . ($data['reason'] ?? 'No reason provided') . ' - ' . date('Y-m-d H:i:s'),
            'updated_at' => Helper::now()
        ]);

        // Log activity
        DB::table('activity_log')->insert([
            'user_id' => $userId,
            'action' => 'cancel',
            'entity_type' => 'lead',
            'entity_id' => $id,
            'description' => "Cancelled lead #{$id}: " . ($data['reason'] ?? 'No reason provided'),
            'created_at' => Helper::now()
        ]);

        $updatedLead = DB::table('leads')
            ->leftJoin('lead_sources', 'leads.source_id', '=', 'lead_sources.id')
            ->select('leads.*', 'lead_sources.name as source_name')
            ->where('leads.id', $id)
            ->first();

        $response->getBody()->write(json_encode([
            'message' => 'Lead cancelled successfully',
            'lead' => $updatedLead
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function convertToBooking(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        $lead = DB::table('leads')->where('id', $id)->first();
        if (!$lead) {
            $response->getBody()->write(json_encode([
                'error' => 'Lead not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Create or find guest
        $guest = DB::table('guests')
            ->where('email', $lead->email)
            ->orWhere('phone', $lead->phone)
            ->first();

        if (!$guest) {
            $guestId = DB::table('guests')->insertGetId([
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'created_at' => Helper::now()
            ]);
        } else {
            $guestId = $guest->id;
        }

        // Create booking (this is a simplified version - full implementation needed)
        $bookingReference = 'GP' . strtoupper(uniqid());
        
        $bookingId = DB::table('bookings')->insertGetId([
            'booking_reference' => $bookingReference,
            'guest_id' => $guestId,
            'lead_id' => $id,
            'unit_id' => $data['unit_id'] ?? 1,
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'number_of_guests' => $data['number_of_guests'] ?? 2,
            'unit_type' => $data['unit_type'] ?? '2_bedroom',
            'status' => $data['status'] ?? 'pending',
            'total_amount' => $data['total_amount'] ?? 0,
            'balance_amount' => $data['total_amount'] ?? 0,
            'created_by' => $userId,
            'created_at' => Helper::now()
        ]);

        // Update lead
        DB::table('leads')->where('id', $id)->update([
            'status' => 'converted',
            'converted_to_booking_id' => $bookingId,
            'updated_at' => Helper::now()
        ]);

        // Log activity
        DB::table('activity_log')->insert([
            'user_id' => $userId,
            'action' => 'convert',
            'entity_type' => 'lead',
            'entity_id' => $id,
            'description' => "Converted lead #{$id} to booking #{$bookingId}",
            'created_at' => Helper::now()
        ]);

        // Get full booking and guest details for email
        $booking = DB::table('bookings')
            ->leftJoin('units', 'bookings.unit_id', '=', 'units.id')
            ->select('bookings.*', 'units.unit_number')
            ->where('bookings.id', $bookingId)
            ->first();

        $guest = DB::table('guests')->where('id', $guestId)->first();

        // Send booking confirmation email if status is confirmed or pending
        $emailSent = false;
        $emailError = null;
        $emailDetails = null;
        if ($booking && $guest && ($booking->status === 'confirmed' || $booking->status === 'pending')) {
            try {
                if (!empty($guest->email)) {
                    // Always try to send email - it's important for customer confirmation
                    if (class_exists('GoldenPalms\CRM\Services\EmailService')) {
                        error_log("Attempting to send booking confirmation email to: {$guest->email}");
                        $emailSent = \GoldenPalms\CRM\Services\EmailService::sendBookingConfirmation($booking, $guest);
                        if (!$emailSent) {
                            $emailError = 'Email service returned false - check server logs for details';
                            error_log("Email service returned false for booking #{$booking->id} to {$guest->email}");
                        } else {
                            error_log("Booking confirmation email sent successfully to: {$guest->email}");
                            $emailDetails = "Email sent to {$guest->email}";
                        }
                    } else {
                        // EmailService not available
                        $emailError = 'EmailService class not found - check autoloading';
                        error_log('EmailService class not found - email not sent. Run: composer dump-autoload');
                    }
                } else {
                    $emailError = 'Guest email is empty - cannot send confirmation email';
                    error_log("Cannot send email: guest email is empty for booking #{$booking->id}");
                }
            } catch (\Throwable $e) {
                // Log error but don't fail the booking creation
                $emailError = $e->getMessage();
                error_log('Failed to send booking confirmation email: ' . $e->getMessage());
                error_log('Exception in: ' . $e->getFile() . ':' . $e->getLine());
                error_log('Stack trace: ' . $e->getTraceAsString());
                $emailSent = false;
            }
        }

        $response->getBody()->write(json_encode([
            'message' => 'Lead converted to booking successfully',
            'booking_id' => $bookingId,
            'booking_reference' => $bookingReference,
            'email_sent' => $emailSent,
            'email_error' => $emailError,
            'email_details' => $emailDetails
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function handleMetaLead(Request $request, Response $response): Response
    {
        // Meta Lead Ads webhook handler
        $data = $request->getParsedBody();
        
        // Verify webhook (Meta sends verification challenge)
        if (isset($data['hub_mode']) && $data['hub_mode'] === 'subscribe') {
            if ($data['hub_verify_token'] === $_ENV['META_LEAD_ADS_VERIFY_TOKEN']) {
                $response->getBody()->write($data['hub_challenge']);
                return $response;
            }
        }

        // Process lead data from Meta
        if (isset($data['entry'])) {
            foreach ($data['entry'] as $entry) {
                if (isset($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        if ($change['field'] === 'leadgen') {
                            $leadData = $change['value'];
                            
                            // Get lead form data
                            $formId = $leadData['form_id'];
                            $leadgenId = $leadData['leadgen_id'];
                            
                            // Fetch full lead data from Meta API (requires API call)
                            // For now, extract from webhook payload
                            $fieldData = $leadData['field_data'] ?? [];
                            
                            $leadInfo = [];
                            foreach ($fieldData as $field) {
                                $leadInfo[$field['name']] = $field['values'][0] ?? '';
                            }

                            // Find Meta Ads source
                            $source = DB::table('lead_sources')
                                ->where('type', 'meta_ads')
                                ->first();

                            if ($source) {
                                $leadId = DB::table('leads')->insertGetId([
                                    'source_id' => $source->id,
                                    'first_name' => $leadInfo['first_name'] ?? $leadInfo['full_name'] ?? 'Unknown',
                                    'last_name' => $leadInfo['last_name'] ?? '',
                                    'email' => $leadInfo['email'] ?? null,
                                    'phone' => $leadInfo['phone_number'] ?? null,
                                    'status' => 'new',
                                    'campaign_name' => $leadData['ad_name'] ?? null,
                                    'ad_set_name' => $leadData['adset_name'] ?? null,
                                    'message' => $leadInfo['message'] ?? null,
                                    'created_at' => Helper::now()
                                ]);

                                // Auto-score the lead
                                \GoldenPalms\CRM\Services\LeadScoringService::updateLeadScore($leadId);

                                // Trigger workflows
                                \GoldenPalms\CRM\Services\WorkflowEngine::processTrigger(
                                    'lead_created',
                                    'lead',
                                    $leadId
                                );
                            }
                        }
                    }
                }
            }
        }

        $response->getBody()->write(json_encode(['status' => 'success']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function handleWebsiteLead(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation error',
                'message' => 'First name and last name are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Determine source based on form type
        $formType = $data['form_type'] ?? 'contact';
        $sourceName = $formType === 'booking' ? 'Website Booking Form' : 'Website Contact Form';
        
        $source = DB::table('lead_sources')
            ->where('name', $sourceName)
            ->first();

        if (!$source) {
            $source = DB::table('lead_sources')
                ->where('type', 'website')
                ->first();
        }

        // Store booking form data in notes as JSON for easy retrieval
        $notes = null;
        if ($formType === 'booking') {
            $bookingData = [
                'check_in' => $data['check_in'] ?? null,
                'check_out' => $data['check_out'] ?? null,
                'unit_type' => $data['unit_type'] ?? null,
                'guests' => $data['guests'] ?? null,
                'number_of_guests' => $data['guests'] ? explode('-', $data['guests'])[1] ?? explode('-', $data['guests'])[0] : null
            ];
            $notes = json_encode($bookingData);
        }
        
        $leadId = DB::table('leads')->insertGetId([
            'source_id' => $source->id ?? 2,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'status' => 'new',
            'form_type' => $formType,
            'message' => $data['message'] ?? null,
            'notes' => $notes,
            'created_at' => Helper::now()
        ]);

        // Auto-score the lead
        \GoldenPalms\CRM\Services\LeadScoringService::updateLeadScore($leadId);

        // Trigger workflows
        \GoldenPalms\CRM\Services\WorkflowEngine::processTrigger(
            'lead_created',
            'lead',
            $leadId
        );

        // Send auto-reply email (implement email service)
        // TODO: Send notification email to staff

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Thank you for your enquiry. We will contact you soon.',
            'lead_id' => $leadId
        ]));

        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }
}

