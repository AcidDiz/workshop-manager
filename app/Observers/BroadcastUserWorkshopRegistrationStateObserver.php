<?php

namespace App\Observers;

use App\Actions\Workshop\BroadcastUserWorkshopRegistrationState;
use App\Models\WorkshopRegistration;

class BroadcastUserWorkshopRegistrationStateObserver
{
    public function __construct(
        private BroadcastUserWorkshopRegistrationState $broadcastUserWorkshopRegistrationState,
    ) {}

    public function created(WorkshopRegistration $registration): void
    {
        $this->broadcast([$registration->workshop_id]);
    }

    public function updated(WorkshopRegistration $registration): void
    {
        if (! $registration->wasChanged(['status', 'workshop_id', 'user_id'])) {
            return;
        }

        $workshopIds = [$registration->workshop_id];
        $clearedStates = [];

        $originalWorkshopId = (int) $registration->getOriginal('workshop_id', $registration->workshop_id);
        $originalUserId = (int) $registration->getOriginal('user_id', $registration->user_id);

        if ($originalWorkshopId !== $registration->workshop_id) {
            $workshopIds[] = $originalWorkshopId;
            $clearedStates[] = [
                'user_id' => $originalUserId,
                'workshop_id' => $originalWorkshopId,
            ];
        } elseif ($originalUserId !== $registration->user_id) {
            $clearedStates[] = [
                'user_id' => $originalUserId,
                'workshop_id' => $registration->workshop_id,
            ];
        }

        $this->broadcast($workshopIds, $clearedStates);
    }

    public function deleted(WorkshopRegistration $registration): void
    {
        $this->broadcast(
            [$registration->workshop_id],
            [[
                'user_id' => $registration->user_id,
                'workshop_id' => $registration->workshop_id,
            ]]
        );
    }

    /**
     * @param  list<int>  $workshopIds
     * @param  list<array{user_id: int, workshop_id: int}>  $clearedStates
     */
    private function broadcast(array $workshopIds, array $clearedStates = []): void
    {
        $this->broadcastUserWorkshopRegistrationState->handle($workshopIds, $clearedStates);
    }
}
