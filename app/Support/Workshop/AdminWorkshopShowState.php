<?php

namespace App\Support\Workshop;

use App\Enums\Workshop\WorkshopRegistrationStatusEnum;
use App\Http\Resources\Workshop\WorkshopParticipantRowResource;
use App\Http\Resources\Workshop\WorkshopShowResource;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use Illuminate\Http\Request;

class AdminWorkshopShowState
{
    /**
     * @return array{
     *     workshop: array<string, mixed>,
     *     canAttachParticipants: bool,
     *     participantList: array<int, array<string, mixed>>,
     *     assignableUsers: array<int, array{id: int, name: string, email: string}>
     * }
     */
    public function resolve(Workshop $workshop): array
    {
        $workshop->load(['category', 'creator']);
        $workshop->loadCount([
            'registrations as confirmed_registrations_count' => function ($query): void {
                $query->where('status', WorkshopRegistrationStatusEnum::Confirmed);
            },
            'registrations as waiting_list_registrations_count' => function ($query): void {
                $query->where('status', WorkshopRegistrationStatusEnum::WaitingList);
            },
        ]);

        $registrations = WorkshopRegistration::query()
            ->where('workshop_id', $workshop->id)
            ->with(['user:id,name,email'])
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [WorkshopRegistrationStatusEnum::Confirmed->value])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $participantList = WorkshopParticipantRowResource::collection($registrations)
            ->resolve(new Request);

        $registeredUserIds = $registrations->pluck('user_id')->unique()->filter()->values()->all();

        $assignableUsers = User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', 'employee');
            })
            ->when(count($registeredUserIds) > 0, function ($query) use ($registeredUserIds): void {
                $query->whereNotIn('id', $registeredUserIds);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->values()
            ->all();

        $confirmedCount = (int) ($workshop->confirmed_registrations_count ?? 0);

        return [
            'workshop' => WorkshopShowResource::make($workshop)->resolve(new Request),
            'canAttachParticipants' => $confirmedCount < (int) $workshop->capacity,
            'participantList' => $participantList,
            'assignableUsers' => $assignableUsers,
        ];
    }
}
