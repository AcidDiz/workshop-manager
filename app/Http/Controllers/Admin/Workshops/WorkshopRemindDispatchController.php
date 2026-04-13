<?php

namespace App\Http\Controllers\Admin\Workshops;

use App\Http\Controllers\Controller;
use App\Models\Workshop;
use App\Services\Workshop\WorkshopReminderService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class WorkshopRemindDispatchController extends Controller
{
    public function __construct(
        private WorkshopReminderService $workshopReminderService,
    ) {}

    public function __invoke(Workshop $workshop): RedirectResponse
    {
        if ($workshop->starts_at->lte(now())) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'This workshop has already started; reminders were not sent.',
            ]);

            return back();
        }

        $count = $this->workshopReminderService->dispatchRemindersForWorkshop($workshop);

        if ($count > 0) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Sent {$count} reminder email(s) to confirmed participants.",
            ]);
        } else {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => 'No confirmed participants to notify for this workshop.',
            ]);
        }

        return back();
    }
}
