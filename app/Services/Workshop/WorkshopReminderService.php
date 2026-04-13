<?php

namespace App\Services\Workshop;

use App\Enums\Workshop\WorkshopRegistrationStatusEnum;
use App\Enums\Workshop\WorkshopReminderKind;
use App\Models\Workshop;
use App\Models\WorkshopReminderDispatch;
use App\Notifications\WorkshopReminderNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class WorkshopReminderService
{
    /**
     * The day before a workshop (by application calendar), notify every confirmed participant.
     * Workshops are included when their {@see Workshop::$starts_at} falls anywhere on the **next calendar day**
     * in {@see config('app.timezone')} (query range is derived as UTC instants for that local day).
     */
    public function dispatchNextCalendarDayReminders(CarbonInterface $now): int
    {
        $tz = config('app.timezone');
        $anchor = Carbon::parse($now)->timezone($tz);
        $dayStart = $anchor->copy()->addDay()->startOfDay()->utc();
        $dayEnd = $anchor->copy()->addDay()->endOfDay()->utc();

        $workshops = Workshop::query()
            ->where('starts_at', '>=', $dayStart)
            ->where('starts_at', '<=', $dayEnd)
            ->whereHas('registrations', fn ($q) => $q->where('status', WorkshopRegistrationStatusEnum::Confirmed))
            ->with([
                'registrations' => fn ($q) => $q
                    ->where('status', WorkshopRegistrationStatusEnum::Confirmed)
                    ->with('user'),
            ])
            ->get();

        return $this->sendReminders($workshops, WorkshopReminderKind::DayBefore, $anchor);
    }

    /**
     * Notify every confirmed participant for a single workshop. Intended for admin-triggered sends from the workshop detail page.
     * Skips when the workshop has already started.
     */
    public function dispatchRemindersForWorkshop(Workshop $workshop): int
    {
        if ($workshop->starts_at->lte(now())) {
            return 0;
        }

        $workshop->loadMissing([
            'registrations' => fn ($q) => $q
                ->where('status', WorkshopRegistrationStatusEnum::Confirmed)
                ->with('user'),
        ]);

        $anchor = now()->clone()->timezone((string) config('app.timezone'));

        return $this->sendReminders(collect([$workshop]), WorkshopReminderKind::AdminManual, $anchor);
    }

    /**
     * @param  Collection<int, Workshop>  $workshops
     */
    private function sendReminders(Collection $workshops, WorkshopReminderKind $kind, CarbonInterface $anchorInAppTz): int
    {
        $dispatchDate = Carbon::parse($anchorInAppTz)->timezone(config('app.timezone'))->toDateString();
        $sent = 0;

        foreach ($workshops as $workshop) {
            foreach ($workshop->registrations as $registration) {
                $user = $registration->user;
                if ($user === null) {
                    continue;
                }

                $inserted = WorkshopReminderDispatch::query()->insertOrIgnore([
                    'workshop_id' => $workshop->id,
                    'user_id' => $user->id,
                    'kind' => $kind->value,
                    'dispatch_date' => $dispatchDate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($inserted === 0) {
                    continue;
                }

                Notification::send($user, new WorkshopReminderNotification($workshop));
                $sent++;
            }
        }

        return $sent;
    }
}
