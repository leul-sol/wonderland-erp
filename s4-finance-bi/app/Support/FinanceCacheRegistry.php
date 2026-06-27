<?php

namespace App\Support;

class FinanceCacheRegistry
{
    /**
     * @var array<string, true>
     */
    private static array $keys = [];

    public static function register(string $key): void
    {
        self::$keys[$key] = true;
    }

    /**
     * @return list<string>
     */
    public static function keysMatchingPrefix(string $prefix): array
    {
        $matches = [];

        foreach (array_keys(self::$keys) as $key) {
            if (str_starts_with($key, $prefix)) {
                $matches[] = $key;
            }
        }

        return $matches;
    }

    public static function reset(): void
    {
        self::$keys = [];
    }
}
