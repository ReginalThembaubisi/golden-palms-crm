<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use GoldenPalms\CRM\Helpers\Helper;

class CampaignController
{
    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $page = (int)($queryParams['page'] ?? 1);
        $perPage = (int)($queryParams['per_page'] ?? 20);
        $status = $queryParams['status'] ?? null;

        $query = DB::table('campaigns')
            ->leftJoin('users', 'campaigns.created_by', '=', 'users.id')
            ->select(
                'campaigns.*',
                'users.first_name as created_by_first_name',
                'users.last_name as created_by_last_name'
            )
            ->orderBy('campaigns.created_at', 'desc');

        if ($status) {
            $query->where('campaigns.status', $status);
        }

        $total = $query->count();
        $campaigns = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $response->getBody()->write(json_encode([
            'data' => $campaigns,
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

        $campaign = DB::table('campaigns')
            ->leftJoin('users', 'campaigns.created_by', '=', 'users.id')
            ->select(
                'campaigns.*',
                'users.first_name as created_by_first_name',
                'users.last_name as created_by_last_name'
            )
            ->where('campaigns.id', $id)
            ->first();

        if (!$campaign) {
            $response->getBody()->write(json_encode([
                'error' => 'Campaign not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if ($campaign->segment) {
            $campaign->segment = json_decode($campaign->segment, true);
        }

        $response->getBody()->write(json_encode($campaign));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        if (empty($data['name']) || empty($data['subject']) || empty($data['content'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Validation error',
                'message' => 'Name, subject, and content are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $campaignId = DB::table('campaigns')->insertGetId([
            'name' => $data['name'],
            'subject' => $data['subject'],
            'content' => $data['content'],
            'template_id' => $data['template_id'] ?? null,
            'type' => $data['type'] ?? 'custom',
            'status' => 'draft',
            'segment' => isset($data['segment']) ? json_encode($data['segment']) : null,
            'scheduled_for' => $data['scheduled_for'] ?? null,
            'created_by' => $userId,
            'created_at' => Helper::now()
        ]);

        return $this->show($request->withAttribute('id', $campaignId), $response, ['id' => $campaignId]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();

        $campaign = DB::table('campaigns')->where('id', $id)->first();
        if (!$campaign) {
            $response->getBody()->write(json_encode([
                'error' => 'Campaign not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $updateData = [];
        $allowedFields = ['name', 'subject', 'content', 'template_id', 'type', 'status', 'segment', 'scheduled_for'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'segment' && is_array($data[$field])) {
                    $updateData[$field] = json_encode($data[$field]);
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }

        $updateData['updated_at'] = Helper::now();

        DB::table('campaigns')->where('id', $id)->update($updateData);

        return $this->show($request, $response, $args);
    }

    public function send(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $userId = $request->getAttribute('user_id');

        $campaign = DB::table('campaigns')->where('id', $id)->first();
        if (!$campaign) {
            $response->getBody()->write(json_encode([
                'error' => 'Campaign not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if ($campaign->status !== 'draft') {
            $response->getBody()->write(json_encode([
                'error' => 'Campaign already sent or in progress'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Get recipients based on segment
        $recipients = $this->getRecipients($campaign);

        if (empty($recipients)) {
            $response->getBody()->write(json_encode([
                'error' => 'No recipients found for this campaign'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Create recipient records
        foreach ($recipients as $recipient) {
            DB::table('campaign_recipients')->insert([
                'campaign_id' => $id,
                'guest_id' => $recipient['guest_id'] ?? null,
                'lead_id' => $recipient['lead_id'] ?? null,
                'email' => $recipient['email'],
                'status' => 'pending',
                'created_at' => Helper::now()
            ]);
        }

        // Update campaign status
        DB::table('campaigns')->where('id', $id)->update([
            'status' => 'sending',
            'total_recipients' => count($recipients),
            'sent_at' => Helper::now()
        ]);

        // TODO: Queue emails for sending (implement email service)
        // For now, just mark as sent
        DB::table('campaigns')->where('id', $id)->update([
            'status' => 'sent',
            'total_sent' => count($recipients)
        ]);

        $response->getBody()->write(json_encode([
            'message' => 'Campaign queued for sending',
            'recipients' => count($recipients)
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function analytics(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];

        $campaign = DB::table('campaigns')->where('id', $id)->first();
        if (!$campaign) {
            $response->getBody()->write(json_encode([
                'error' => 'Campaign not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $recipients = DB::table('campaign_recipients')
            ->where('campaign_id', $id)
            ->get();

        $analytics = [
            'total_recipients' => $campaign->total_recipients,
            'total_sent' => $campaign->total_sent,
            'total_delivered' => $recipients->where('status', 'delivered')->count(),
            'total_opened' => $campaign->total_opened,
            'total_clicked' => $campaign->total_clicked,
            'total_bounced' => $campaign->total_bounced,
            'total_unsubscribed' => $campaign->total_unsubscribed,
            'open_rate' => $campaign->total_sent > 0 
                ? round(($campaign->total_opened / $campaign->total_sent) * 100, 2) 
                : 0,
            'click_rate' => $campaign->total_sent > 0 
                ? round(($campaign->total_clicked / $campaign->total_sent) * 100, 2) 
                : 0,
            'bounce_rate' => $campaign->total_sent > 0 
                ? round(($campaign->total_bounced / $campaign->total_sent) * 100, 2) 
                : 0
        ];

        $response->getBody()->write(json_encode($analytics));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getRecipients($campaign): array
    {
        $segment = $campaign->segment ? json_decode($campaign->segment, true) : null;
        $recipients = [];

        if (!$segment) {
            // No segment - get all guests and leads with emails
            $guests = DB::table('guests')
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get();

            foreach ($guests as $guest) {
                $recipients[] = [
                    'guest_id' => $guest->id,
                    'email' => $guest->email
                ];
            }

            $leads = DB::table('leads')
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->where('status', '!=', 'lost')
                ->get();

            foreach ($leads as $lead) {
                $recipients[] = [
                    'lead_id' => $lead->id,
                    'email' => $lead->email
                ];
            }
        } else {
            // Apply segment filters
            $query = DB::table('guests')->whereNotNull('email')->where('email', '!=', '');

            if (isset($segment['tags']) && is_array($segment['tags'])) {
                // Filter by tags (simplified - would need JSON search in production)
            }

            if (isset($segment['has_bookings'])) {
                $query->whereRaw('id IN (SELECT DISTINCT guest_id FROM bookings)');
            }

            $guests = $query->get();
            foreach ($guests as $guest) {
                $recipients[] = [
                    'guest_id' => $guest->id,
                    'email' => $guest->email
                ];
            }
        }

        return $recipients;
    }
}

