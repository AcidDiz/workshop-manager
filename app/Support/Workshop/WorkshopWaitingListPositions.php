<?php

namespace App\Support\Workshop;

use App\Enums\Workshop\WorkshopRegistrationStatusEnum;
use App\Models\WorkshopRegistration;
use Illuminate\Support\Collection;

class WorkshopWaitingListPositions
{
    /**
     * @param  Collection<int, WorkshopRegistration>  $registrations
     * @return array<int, int>
     */
    public static function forUserRegistrations(Collection $registrations): array
    {
        $waitingRegistrations = $registrations
            ->filter(fn (WorkshopRegistration $registration): bool => $registration->status === WorkshopRegistrationStatusEnum::WaitingList)
            ->values();

        if ($waitingRegistrations->isEmpty()) {
            return [];
        }

        $orderedWaiting = WorkshopRegistration::query()
            ->whereIn('workshop_id', $waitingRegistrations->pluck('workshop_id')->unique()->all())
            ->where('status', WorkshopRegistrationStatusEnum::WaitingList)
            ->orderBy('workshop_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $positionsByRegistrationId = [];

        foreach ($orderedWaiting->groupBy('workshop_id') as $rows) {
            foreach ($rows->values() as $index => $row) {
                $positionsByRegistrationId[$row->id] = $index + 1;
            }
        }

        return $waitingRegistrations
            ->mapWithKeys(fn (WorkshopRegistration $registration): array => [
                $registration->workshop_id => $positionsByRegistrationId[$registration->id] ?? 1,
            ])
            ->all();
    }
}
