<?php

namespace App\Services\Workshop;

use App\Enums\Workshop\WorkshopRegistrationStatusEnum;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use Illuminate\Support\Carbon;

class WorkshopStatisticsService
{
    /**
     * Compact snapshot for the admin dashboard and JSON polling endpoint.
     *
     * @return array{
     *     workshops: array{total: int, upcoming: int, closed: int},
     *     registrations: array{confirmed: int, waiting_list: int, total: int},
     *     next_upcoming_workshop: null|array{id: int, title: string, starts_at: string, ends_at: string, confirmed_registrations_count: int, capacity: int},
     *     generated_at: string
     * }
     */
    public function snapshot(?Carbon $now = null): array
    {
        $now ??= now();

        $workshopStats = Workshop::query()
            ->toBase()
            ->selectRaw(
                'COUNT(*) as total, SUM(CASE WHEN starts_at > ? THEN 1 ELSE 0 END) as upcoming, SUM(CASE WHEN starts_at <= ? THEN 1 ELSE 0 END) as closed',
                [$now, $now]
            )
            ->first();

        $registrationRows = WorkshopRegistration::query()
            ->toBase()
            ->select('status')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('status')
            ->get();

        $confirmedRow = $registrationRows->firstWhere('status', WorkshopRegistrationStatusEnum::Confirmed->value);
        $waitingRow = $registrationRows->firstWhere('status', WorkshopRegistrationStatusEnum::WaitingList->value);
        $confirmedRegistrations = (int) ($confirmedRow?->cnt ?? 0);
        $waitingListRegistrations = (int) ($waitingRow?->cnt ?? 0);

        $nextUpcomingWorkshop = Workshop::query()
            ->withConfirmedRegistrationCount()
            ->where('starts_at', '>', $now)
            ->orderBy('starts_at')
            ->orderBy('id')
            ->first();

        $nextUpcomingPayload = null;
        if ($nextUpcomingWorkshop !== null) {
            $nextUpcomingPayload = [
                'id' => $nextUpcomingWorkshop->id,
                'title' => $nextUpcomingWorkshop->title,
                'starts_at' => $nextUpcomingWorkshop->starts_at->toIso8601String(),
                'ends_at' => $nextUpcomingWorkshop->ends_at->toIso8601String(),
                'confirmed_registrations_count' => (int) $nextUpcomingWorkshop->confirmed_registrations_count,
                'capacity' => (int) $nextUpcomingWorkshop->capacity,
            ];
        }

        return [
            'workshops' => [
                'total' => (int) ($workshopStats->total ?? 0),
                'upcoming' => (int) ($workshopStats->upcoming ?? 0),
                'closed' => (int) ($workshopStats->closed ?? 0),
            ],
            'registrations' => [
                'confirmed' => $confirmedRegistrations,
                'waiting_list' => $waitingListRegistrations,
                'total' => $confirmedRegistrations + $waitingListRegistrations,
            ],
            'next_upcoming_workshop' => $nextUpcomingPayload,
            'generated_at' => $now->toIso8601String(),
        ];
    }
}
