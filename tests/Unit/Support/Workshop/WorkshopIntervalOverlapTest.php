<?php

use App\Support\Workshop\WorkshopIntervalOverlap;
use Carbon\Carbon;

test('intervals overlap when ranges intersect', function () {
    $aStart = Carbon::parse('2026-05-01 10:00:00');
    $aEnd = Carbon::parse('2026-05-01 12:00:00');
    $bStart = Carbon::parse('2026-05-01 11:00:00');
    $bEnd = Carbon::parse('2026-05-01 13:00:00');

    expect(WorkshopIntervalOverlap::intervalsOverlap($aStart, $aEnd, $bStart, $bEnd))->toBeTrue();
});

test('adjacent intervals do not overlap', function () {
    $aStart = Carbon::parse('2026-05-01 10:00:00');
    $aEnd = Carbon::parse('2026-05-01 12:00:00');
    $bStart = Carbon::parse('2026-05-01 12:00:00');
    $bEnd = Carbon::parse('2026-05-01 14:00:00');

    expect(WorkshopIntervalOverlap::intervalsOverlap($aStart, $aEnd, $bStart, $bEnd))->toBeFalse();
});
