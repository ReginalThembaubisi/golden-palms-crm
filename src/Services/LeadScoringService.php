<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Services;

use Illuminate\Database\Capsule\Manager as DB;

class LeadScoringService
{
    /**
     * Calculate lead score (0-100)
     */
    public static function calculateScore(int $leadId): int
    {
        $lead = DB::table('leads')
            ->leftJoin('lead_sources', 'leads.source_id', '=', 'lead_sources.id')
            ->where('leads.id', $leadId)
            ->first();

        if (!$lead) {
            return 0;
        }

        $score = 0;

        // Source Quality (0-25 points)
        $score += self::getSourceScore($lead->type ?? '');

        // Information Completeness (0-20 points)
        $score += self::getCompletenessScore($lead);

        // Engagement Level (0-25 points)
        $score += self::getEngagementScore($leadId);

        // Time Factor (0-10 points)
        $score += self::getTimeScore($lead->created_at ?? '');

        // Historical Conversion Data (0-20 points)
        $score += self::getHistoricalScore($lead);

        // Ensure score is between 0-100
        return min(100, max(0, $score));
    }

    private static function getSourceScore(string $sourceType): int
    {
        $scores = [
            'meta_ads' => 20,  // High quality, paid leads
            'phone' => 18,     // Direct contact, high intent
            'website' => 15,   // Good quality, organic
            'email' => 12,     // Medium quality
            'manual' => 10,    // Lower quality
            'other' => 5,
        ];

        return $scores[$sourceType] ?? 5;
    }

    private static function getCompletenessScore($lead): int
    {
        $score = 0;

        // Has both email and phone (best)
        if (!empty($lead->email) && !empty($lead->phone)) {
            $score += 15;
        }
        // Has email only
        elseif (!empty($lead->email)) {
            $score += 10;
        }
        // Has phone only
        elseif (!empty($lead->phone)) {
            $score += 8;
        }

        // Has message/notes (shows engagement)
        if (!empty($lead->message) || !empty($lead->notes)) {
            $score += 5;
        }

        return $score;
    }

    private static function getEngagementScore(int $leadId): int
    {
        $score = 0;

        // Check communications
        $communications = DB::table('communications')
            ->where('lead_id', $leadId)
            ->get();

        foreach ($communications as $comm) {
            if ($comm->direction === 'inbound') {
                $score += 5; // Inbound communication is positive
            }
            if ($comm->status === 'read') {
                $score += 3; // Email was read
            }
        }

        // Check if contacted
        $lead = DB::table('leads')->where('id', $leadId)->first();
        if ($lead && $lead->contacted_at) {
            $score += 10; // Has been contacted
        }

        // Check status progression
        if ($lead && $lead->status === 'qualified') {
            $score += 7; // Qualified leads are higher value
        }

        return min(25, $score); // Cap at 25
    }

    private static function getTimeScore(string $createdAt): int
    {
        if (empty($createdAt)) {
            return 0;
        }

        $created = new \DateTime($createdAt);
        $now = new \DateTime();
        $hoursSinceCreation = ($now->getTimestamp() - $created->getTimestamp()) / 3600;

        // Fresh leads (within 24 hours) get bonus
        if ($hoursSinceCreation < 24) {
            return 10;
        }
        // Recent leads (within 7 days)
        elseif ($hoursSinceCreation < 168) {
            return 5;
        }
        // Older leads
        else {
            return 0;
        }
    }

    private static function getHistoricalScore($lead): int
    {
        $score = 0;

        // Check if similar leads converted
        if (!empty($lead->email)) {
            $similarLeads = DB::table('leads')
                ->where('email', $lead->email)
                ->where('id', '!=', $lead->id)
                ->where('status', 'converted')
                ->count();

            if ($similarLeads > 0) {
                $score += 15; // Previous conversion
            }
        }

        // Check source conversion rate
        $sourceConversions = DB::table('leads')
            ->where('source_id', $lead->source_id)
            ->where('status', 'converted')
            ->count();

        $sourceTotal = DB::table('leads')
            ->where('source_id', $lead->source_id)
            ->count();

        if ($sourceTotal > 10) {
            $conversionRate = ($sourceConversions / $sourceTotal) * 100;
            // High conversion rate source gets bonus
            if ($conversionRate > 20) {
                $score += 5;
            }
        }

        return min(20, $score); // Cap at 20
    }

    /**
     * Update lead score and priority
     */
    public static function updateLeadScore(int $leadId): void
    {
        $score = self::calculateScore($leadId);

        // Determine priority based on score
        $priority = 'low';
        if ($score >= 70) {
            $priority = 'high';
        } elseif ($score >= 40) {
            $priority = 'medium';
        }

        DB::table('leads')
            ->where('id', $leadId)
            ->update([
                'quality_score' => $score,
                'priority' => $priority,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Recalculate scores for all leads (useful for batch updates)
     */
    public static function recalculateAllScores(): int
    {
        $leads = DB::table('leads')->select('id')->get();
        $count = 0;

        foreach ($leads as $lead) {
            self::updateLeadScore($lead->id);
            $count++;
        }

        return $count;
    }
}


