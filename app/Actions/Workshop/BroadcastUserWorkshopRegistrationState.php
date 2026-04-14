<?php

namespace App\Actions\Workshop;

use App\Enums\Workshop\WorkshopRegistrationStatusEnum;
use App\Events\Workshop\UserWorkshopRegistrationStateUpdated;
use App\Models\WorkshopRegistration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BroadcastUserWorkshopRegistrationState
{
    /**
     * @param  list<int>  $workshopIds
     * @param  list<array{user_id: int, workshop_id: int}>  $clearedStates
     */
    public function handle(array $workshopIds, array $clearedStates = []): void
    {
        $workshopIds = array_values(array_unique(array_filter(
            $workshopIds,
            fn (int $id): bool => $id > 0,
        )));

        if ($workshopIds === [] && $clearedStates === []) {
            return;
        }

        DB::afterCommit(function () use ($workshopIds, $clearedStates): void {
            foreach ($this->currentStatesForWorkshops($workshopIds) as $state) {
                broadcast(new UserWorkshopRegistrationStateUpdated(
                    userId: $state['user_id'],
                    workshopId: $state['workshop_id'],
                    registrationStatus: $state['registration_status'],
                    waitingListPosition: $state['waiting_list_position'],
                ));
            }

            foreach ($clearedStates as $state) {
                broadcast(new UserWorkshopRegistrationStateUpdated(
                    userId: $state['user_id'],
                    workshopId: $state['workshop_id'],
                    registrationStatus: null,
                    waitingListPosition: null,
                ));
            }
        });
    }

    /**
     * @param  list<int>  $workshopIds
     * @return list<array{user_id: int, workshop_id: int, registration_status: string, waiting_list_position: ?int}>
     */
    private function currentStatesForWorkshops(array $workshopIds): array
    {
        if ($workshopIds === []) {
            return [];
        }

        return WorkshopRegistration::query()
            ->whereIn('workshop_id', $workshopIds)
            ->orderBy('workshop_id')
            ->orderByRaw(
                'CASE WHEN status = ? THEN 0 ELSE 1 END',
                [WorkshopRegistrationStatusEnum::WaitingList->value]
            )
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('workshop_id')
            ->flatMap(function (Collection $rows): array {
                $waitingListPosition = 0;

                return $rows->map(function (WorkshopRegistration $registration) use (&$waitingListPosition): array {
                    $position = null;
                    if ($registration->status === WorkshopRegistrationStatusEnum::WaitingList) {
                        $waitingListPosition += 1;
                        $position = $waitingListPosition;
                    }

                    return [
                        'user_id' => $registration->user_id,
                        'workshop_id' => $registration->workshop_id,
                        'registration_status' => $registration->status->value,
                        'waiting_list_position' => $position,
                    ];
                })->all();
            })
            ->values()
            ->all();
    }
}
