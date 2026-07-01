<?php

namespace App\Support;

class FolioChargeCategories
{
    /** @var list<string> */
    public const ALL = ['room', 'fb', 'minibar', 'laundry', 'event', 'other'];

    public static function validationRule(): string
    {
        return 'in:'.implode(',', self::ALL);
    }
}
