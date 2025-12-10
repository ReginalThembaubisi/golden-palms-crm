<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use GoldenPalms\CRM\Helpers\Helper;

class DashboardController
{
    public function getStats(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $period = $queryParams['period'] ?? 'month'; // day, week, month, year
        $dateFrom = $queryParams['date_from'] ?? null;
        $dateTo = $queryParams['date_to'] ?? null;

        // Set date range based on period
        if (!$dateFrom || !$dateTo) {
            [$dateFrom, $dateTo] = $this->getDateRange($period);
        }

        $stats = [
            'period' => $period,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'leads' => $this->getLeadStats($dateFrom, $dateTo),
            'bookings' => $this->getBookingStats($dateFrom, $dateTo),
            'revenue' => $this->getRevenueStats($dateFrom, $dateTo),
            'guests' => $this->getGuestStats($dateFrom, $dateTo),
            'campaigns' => $this->getCampaignStats($dateFrom, $dateTo),
            'reviews' => $this->getReviewStats($dateFrom, $dateTo),
            'trends' => $this->getTrends($dateFrom, $dateTo),
            'upcoming_events' => $this->getUpcomingEvents(),
        ];

        $response->getBody()->write(json_encode($stats));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getDateRange(string $period): array
    {
        $today = date('Y-m-d');
        
        switch ($period) {
            case 'day':
                return [$today, $today];
            case 'week':
                $start = date('Y-m-d', strtotime('monday this week'));
                return [$start, $today];
            case 'month':
                $start = date('Y-m-01');
                return [$start, $today];
            case 'year':
                $start = date('Y-01-01');
                return [$start, $today];
            default:
                $start = date('Y-m-01');
                return [$start, $today];
        }
    }

    private function getLeadStats(string $dateFrom, string $dateTo): array
    {
        $total = DB::table('leads')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->count();

        $new = DB::table('leads')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', 'new')
            ->count();

        $converted = DB::table('leads')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', 'converted')
            ->count();

        $conversionRate = $total > 0 ? round(($converted / $total) * 100, 2) : 0;

        // Average response time (in hours)
        $avgResponseTime = DB::table('leads')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->whereNotNull('contacted_at')
            ->whereNotNull('created_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, contacted_at)) as avg_hours')
            ->value('avg_hours') ?? 0;

        // By source
        $bySource = DB::table('leads')
            ->join('lead_sources', 'leads.source_id', '=', 'lead_sources.id')
            ->whereBetween('leads.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->select('lead_sources.name', 'lead_sources.type', 'lead_sources.color', DB::raw('COUNT(*) as count'))
            ->groupBy('lead_sources.id', 'lead_sources.name', 'lead_sources.type', 'lead_sources.color')
            ->get();

        // By status
        $byStatus = DB::table('leads')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        // High priority leads
        $highPriority = DB::table('leads')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('priority', 'high')
            ->count();

        return [
            'total' => $total,
            'new' => $new,
            'converted' => $converted,
            'conversion_rate' => $conversionRate,
            'avg_response_time_hours' => round((float)$avgResponseTime, 2),
            'high_priority' => $highPriority,
            'by_source' => $bySource,
            'by_status' => $byStatus,
        ];
    }

    private function getBookingStats(string $dateFrom, string $dateTo): array
    {
        $total = DB::table('bookings')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->count();

        $confirmed = DB::table('bookings')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', 'confirmed')
            ->count();

        $checkedIn = DB::table('bookings')
            ->whereBetween('check_in', [$dateFrom, $dateTo])
            ->where('status', 'checked_in')
            ->count();

        $cancelled = DB::table('bookings')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', 'cancelled')
            ->count();

        $cancellationRate = $total > 0 ? round(($cancelled / $total) * 100, 2) : 0;

        // Upcoming check-ins (next 7 days)
        $today = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        $upcomingCheckIns = DB::table('bookings')
            ->whereBetween('check_in', [$today, $nextWeek])
            ->whereIn('status', ['confirmed', 'pending'])
            ->count();

        // Current occupancy
        $currentOccupancy = DB::table('bookings')
            ->where('check_in', '<=', $today)
            ->where('check_out', '>=', $today)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->count();

        $totalUnits = DB::table('units')->where('is_active', 1)->count();
        $occupancyRate = $totalUnits > 0 ? round(($currentOccupancy / $totalUnits) * 100, 2) : 0;

        // By status
        $byStatus = DB::table('bookings')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'checked_in' => $checkedIn,
            'cancelled' => $cancelled,
            'cancellation_rate' => $cancellationRate,
            'upcoming_check_ins' => $upcomingCheckIns,
            'current_occupancy' => $currentOccupancy,
            'occupancy_rate' => $occupancyRate,
            'by_status' => $byStatus,
        ];
    }

    private function getRevenueStats(string $dateFrom, string $dateTo): array
    {
        $totalRevenue = (float)(DB::table('bookings')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount') ?? 0);

        $paidRevenue = (float)(DB::table('bookings')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', '!=', 'cancelled')
            ->where('payment_status', 'paid')
            ->sum('total_amount') ?? 0);

        $pendingRevenue = (float)(DB::table('bookings')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', '!=', 'cancelled')
            ->where('payment_status', '!=', 'paid')
            ->sum('balance_amount') ?? 0);

        // Average booking value
        $avgBookingValue = (float)(DB::table('bookings')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', '!=', 'cancelled')
            ->avg('total_amount') ?? 0);

        // Revenue by unit type
        $byUnitType = DB::table('bookings')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', '!=', 'cancelled')
            ->select('unit_type', DB::raw('SUM(total_amount) as revenue'), DB::raw('COUNT(*) as count'))
            ->groupBy('unit_type')
            ->get();

        // Revenue trend (daily)
        $revenueTrend = DB::table('bookings')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as bookings')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total_revenue' => round($totalRevenue, 2),
            'paid_revenue' => round($paidRevenue, 2),
            'pending_revenue' => round($pendingRevenue, 2),
            'avg_booking_value' => round($avgBookingValue, 2),
            'by_unit_type' => $byUnitType,
            'trend' => $revenueTrend,
        ];
    }

    private function getGuestStats(string $dateFrom, string $dateTo): array
    {
        $newGuests = DB::table('guests')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->count();

        $repeatGuests = DB::table('guests')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('total_nights', '>', 0)
            ->count();

        $totalGuests = DB::table('guests')->count();
        $totalRepeatGuests = DB::table('guests')->where('total_nights', '>', 0)->count();
        $repeatRate = $totalGuests > 0 ? round(($totalRepeatGuests / $totalGuests) * 100, 2) : 0;

        // Top guests by revenue
        $topGuests = DB::table('guests')
            ->join('bookings', 'guests.id', '=', 'bookings.guest_id')
            ->whereBetween('bookings.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('bookings.status', '!=', 'cancelled')
            ->select(
                'guests.id',
                'guests.first_name',
                'guests.last_name',
                'guests.email',
                DB::raw('SUM(bookings.total_amount) as total_revenue'),
                DB::raw('COUNT(bookings.id) as booking_count')
            )
            ->groupBy('guests.id', 'guests.first_name', 'guests.last_name', 'guests.email')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get();

        return [
            'new_guests' => $newGuests,
            'repeat_guests' => $repeatGuests,
            'total_guests' => $totalGuests,
            'repeat_rate' => $repeatRate,
            'top_guests' => $topGuests,
        ];
    }

    private function getCampaignStats(string $dateFrom, string $dateTo): array
    {
        $totalCampaigns = DB::table('campaigns')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->count();

        $sentCampaigns = DB::table('campaigns')
            ->whereBetween('sent_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', 'sent')
            ->count();

        $totalSent = DB::table('campaign_recipients')
            ->join('campaigns', 'campaign_recipients.campaign_id', '=', 'campaigns.id')
            ->whereBetween('campaigns.sent_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('campaign_recipients.status', 'sent')
            ->count();

        $totalOpened = DB::table('campaign_recipients')
            ->join('campaigns', 'campaign_recipients.campaign_id', '=', 'campaigns.id')
            ->whereBetween('campaigns.sent_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('campaign_recipients.status', 'opened')
            ->count();

        $openRate = $totalSent > 0 ? round(($totalOpened / $totalSent) * 100, 2) : 0;

        return [
            'total_campaigns' => $totalCampaigns,
            'sent_campaigns' => $sentCampaigns,
            'total_sent' => $totalSent,
            'total_opened' => $totalOpened,
            'open_rate' => $openRate,
        ];
    }

    private function getReviewStats(string $dateFrom, string $dateTo): array
    {
        $totalReviews = DB::table('reviews')
            ->whereBetween('reviewed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->count();

        $avgRating = (float)(DB::table('reviews')
            ->whereBetween('reviewed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->avg('rating') ?? 0);

        $totalRequests = DB::table('review_requests')
            ->whereBetween('sent_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->count();

        $reviewedRequests = DB::table('review_requests')
            ->whereBetween('sent_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', 'reviewed')
            ->count();

        $reviewRate = $totalRequests > 0 ? round(($reviewedRequests / $totalRequests) * 100, 2) : 0;

        // Rating distribution
        $ratingDistribution = DB::table('reviews')
            ->whereBetween('reviewed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->select('rating', DB::raw('COUNT(*) as count'))
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get();

        return [
            'total_reviews' => $totalReviews,
            'avg_rating' => round($avgRating, 2),
            'total_requests' => $totalRequests,
            'review_rate' => $reviewRate,
            'rating_distribution' => $ratingDistribution,
        ];
    }

    private function getTrends(string $dateFrom, string $dateTo): array
    {
        // Daily trends for the period
        $leadTrends = DB::table('leads')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $bookingTrends = DB::table('bookings')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'leads' => $leadTrends,
            'bookings' => $bookingTrends,
        ];
    }

    private function getUpcomingEvents(): array
    {
        $today = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime('+7 days'));

        // Upcoming check-ins
        $upcomingCheckIns = DB::table('bookings')
            ->join('guests', 'bookings.guest_id', '=', 'guests.id')
            ->join('units', 'bookings.unit_id', '=', 'units.id')
            ->whereBetween('bookings.check_in', [$today, $nextWeek])
            ->whereIn('bookings.status', ['confirmed', 'pending'])
            ->select(
                'bookings.id',
                'bookings.booking_reference',
                'bookings.check_in',
                'guests.first_name',
                'guests.last_name',
                'units.unit_number',
                'bookings.number_of_guests'
            )
            ->orderBy('bookings.check_in')
            ->limit(10)
            ->get();

        // Upcoming check-outs
        $upcomingCheckOuts = DB::table('bookings')
            ->join('guests', 'bookings.guest_id', '=', 'guests.id')
            ->join('units', 'bookings.unit_id', '=', 'units.id')
            ->whereBetween('bookings.check_out', [$today, $nextWeek])
            ->whereIn('bookings.status', ['confirmed', 'checked_in'])
            ->select(
                'bookings.id',
                'bookings.booking_reference',
                'bookings.check_out',
                'guests.first_name',
                'guests.last_name',
                'units.unit_number'
            )
            ->orderBy('bookings.check_out')
            ->limit(10)
            ->get();

        // Uncontacted leads (new leads not contacted in 24 hours)
        $uncontactedLeads = DB::table('leads')
            ->join('lead_sources', 'leads.source_id', '=', 'lead_sources.id')
            ->where('leads.status', 'new')
            ->whereNull('leads.contacted_at')
            ->whereRaw('TIMESTAMPDIFF(HOUR, leads.created_at, NOW()) > 24')
            ->select(
                'leads.id',
                'leads.first_name',
                'leads.last_name',
                'leads.email',
                'leads.created_at',
                'lead_sources.name as source_name'
            )
            ->orderBy('leads.created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'upcoming_check_ins' => $upcomingCheckIns,
            'upcoming_check_outs' => $upcomingCheckOuts,
            'uncontacted_leads' => $uncontactedLeads,
        ];
    }
}


