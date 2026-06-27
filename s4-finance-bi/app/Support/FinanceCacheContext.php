<?php

namespace App\Support;

class FinanceCacheContext
{
    private static bool $stale = false;

    public static function markStale(): void
    {
        self::$stale = true;
    }

    public static function isStale(): bool
    {
        return self::$stale;
    }

    public static function reset(): void
    {
        self::$stale = false;
    }
}
