<?php

declare(strict_types=1);

namespace GoldenPalms\CRM\Helpers;

class Helper
{
    /**
     * Get current timestamp in MySQL format
     */
    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Generate unique booking reference
     */
    public static function generateBookingReference(): string
    {
        return 'GP' . strtoupper(uniqid());
    }
}

