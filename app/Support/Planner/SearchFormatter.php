<?php

namespace App\Support\Planner;

class SearchFormatter
{
    public static function clean(?string $search): ?string
    {
        if (!$search) return null;

        // Remove trailing " (community)" or " (Username)"
        return trim(preg_replace('/\s*\([^)]+\)$/', '', trim($search)));
    }
}
