<?php

namespace App\Http\Controllers\Admin\Workshops;

use App\Http\Controllers\Controller;
use App\Services\Workshop\WorkshopReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class WorkshopNextDayRemindDispatchController extends Controller
{
    public function __construct(
        private WorkshopReminderService $workshopReminderService,
    ) {}

    public function __invoke(): RedirectResponse
    {
        $count = $this->workshopReminderService->dispatchNextCalendarDayReminders(Carbon::now());

        if ($count > 0) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Sent {$count} reminder email(s) for workshops starting tomorrow.",
            ]);
        } else {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => 'No reminder emails were sent (no confirmed registrations for workshops starting tomorrow).',
            ]);
        }

        return back();
    }
}
