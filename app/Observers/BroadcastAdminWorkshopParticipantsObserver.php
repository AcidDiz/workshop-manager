<?php

namespace App\Observers;

use App\Actions\Workshop\BroadcastAdminWorkshopParticipants;
use App\Models\WorkshopRegistration;

class BroadcastAdminWorkshopParticipantsObserver
{
    public function __construct(
        private BroadcastAdminWorkshopParticipants $broadcastAdminWorkshopParticipants,
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
        $originalWorkshopId = (int) $registration->getOriginal('workshop_id', $registration->workshop_id);

        if ($originalWorkshopId !== $registration->workshop_id) {
            $workshopIds[] = $originalWorkshopId;
        }

        $this->broadcast($workshopIds);
    }

    public function deleted(WorkshopRegistration $registration): void
    {
        $this->broadcast([$registration->workshop_id]);
    }

    /**
     * @param  list<int>  $workshopIds
     */
    private function broadcast(array $workshopIds): void
    {
        $this->broadcastAdminWorkshopParticipants->handle($workshopIds);
    }
}
