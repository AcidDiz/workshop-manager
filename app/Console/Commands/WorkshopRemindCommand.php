<?php

namespace App\Console\Commands;

use App\Services\Workshop\WorkshopReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class WorkshopRemindCommand extends Command
{
    protected $signature = 'workshops:remind';

    protected $description = 'Send email reminders to confirmed participants for workshops starting the next calendar day';

    public function handle(WorkshopReminderService $reminders): int
    {
        $count = $reminders->dispatchNextCalendarDayReminders(Carbon::now());
        $this->info("{$count} reminder(s) sent.");

        return self::SUCCESS;
    }
}
