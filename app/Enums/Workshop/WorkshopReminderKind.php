<?php

namespace App\Enums\Workshop;

enum WorkshopReminderKind: string
{
    case DayBefore = 'day_before';
    case AdminManual = 'admin_manual';
}
