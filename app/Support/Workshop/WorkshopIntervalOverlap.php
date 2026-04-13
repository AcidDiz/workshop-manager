<?php

namespace App\Support\Workshop;

use App\Models\Workshop;
use Carbon\CarbonInterface;

final class WorkshopIntervalOverlap
{
    /**
     * Standard interval overlap on [starts_at, ends_at).
     */
    public static function workshopsOverlap(Workshop $a, Workshop $b): bool
    {
        return self::intervalsOverlap(
            $a->starts_at,
            $a->ends_at,
            $b->starts_at,
            $b->ends_at,
        );
    }

    public static function intervalsOverlap(
        CarbonInterface $aStart,
        CarbonInterface $aEnd,
        CarbonInterface $bStart,
        CarbonInterface $bEnd,
    ): bool {
        return $aStart->lt($bEnd) && $bStart->lt($aEnd);
    }
}
