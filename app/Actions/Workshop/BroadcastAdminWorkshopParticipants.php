<?php

namespace App\Actions\Workshop;

use App\Events\Workshop\AdminWorkshopParticipantsUpdated;
use App\Models\Workshop;
use App\Support\Workshop\AdminWorkshopShowState;
use Illuminate\Support\Facades\DB;

class BroadcastAdminWorkshopParticipants
{
    public function __construct(
        private AdminWorkshopShowState $adminWorkshopShowState,
    ) {}

    /**
     * @param  list<int>  $workshopIds
     */
    public function handle(array $workshopIds): void
    {
        $workshopIds = array_values(array_unique(array_filter($workshopIds, fn (int $id): bool => $id > 0)));

        if ($workshopIds === []) {
            return;
        }

        DB::afterCommit(function () use ($workshopIds): void {
            $workshops = Workshop::query()
                ->whereIn('id', $workshopIds)
                ->get();

            foreach ($workshops as $workshop) {
                broadcast(new AdminWorkshopParticipantsUpdated(
                    workshopId: $workshop->id,
                    state: $this->adminWorkshopShowState->resolve($workshop),
                ));
            }
        });
    }
}
