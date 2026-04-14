<?php

namespace App\Services\Workshop;

use App\Enums\Workshop\WorkshopRegistrationStatusEnum;
use App\Exceptions\Workshop\WorkshopRegistrationException;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Support\Workshop\WorkshopIntervalOverlap;
use Illuminate\Support\Facades\DB;

class WorkshopRegistrationService
{
    public function attach(User $user, Workshop $workshop): WorkshopRegistration
    {
        return $this->enrolUser($user, $workshop, selfService: true);
    }

    /**
     * Admin-managed enrolment: any employee can be added when there is a free confirmed seat, even
     * when the workshop is no longer “open” for self-service; still enforces duplicate and
     * schedule-overlap rules for the subject user. Does not add waiting-list rows.
     */
    public function attachAsAdmin(User $subject, Workshop $workshop): WorkshopRegistration
    {
        return $this->enrolUser($subject, $workshop, selfService: false);
    }

    private function enrolUser(User $user, Workshop $workshop, bool $selfService): WorkshopRegistration
    {
        return DB::transaction(function () use ($user, $workshop, $selfService) {
            $workshop = Workshop::query()->whereKey($workshop->id)->lockForUpdate()->firstOrFail();

            if ($selfService && ! $workshop->starts_at->isFuture()) {
                throw WorkshopRegistrationException::workshopClosed();
            }

            $existing = WorkshopRegistration::query()
                ->where('workshop_id', $workshop->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                throw $selfService
                    ? WorkshopRegistrationException::alreadyRegistered()
                    : WorkshopRegistrationException::subjectAlreadyRegistered();
            }

            $this->assertNoScheduleOverlapWithExistingRegistrations($user, $workshop, adminSubject: ! $selfService);

            $confirmedCount = WorkshopRegistration::query()
                ->where('workshop_id', $workshop->id)
                ->where('status', WorkshopRegistrationStatusEnum::Confirmed)
                ->count();

            if (! $selfService && $confirmedCount >= $workshop->capacity) {
                throw WorkshopRegistrationException::workshopFullForAdminAttach();
            }

            $status = $confirmedCount < $workshop->capacity
                ? WorkshopRegistrationStatusEnum::Confirmed
                : WorkshopRegistrationStatusEnum::WaitingList;

            return WorkshopRegistration::query()->create([
                'workshop_id' => $workshop->id,
                'user_id' => $user->id,
                'status' => $status,
            ]);
        });
    }

    private function assertNoScheduleOverlapWithExistingRegistrations(User $user, Workshop $workshop, bool $adminSubject): void
    {
        $otherRegistrations = WorkshopRegistration::query()
            ->where('user_id', $user->id)
            ->where('workshop_id', '!=', $workshop->id)
            ->with('workshop')
            ->lockForUpdate()
            ->get();

        foreach ($otherRegistrations as $registration) {
            $otherWorkshop = $registration->workshop;
            if ($otherWorkshop === null) {
                continue;
            }

            if (WorkshopIntervalOverlap::workshopsOverlap($workshop, $otherWorkshop)) {
                throw $adminSubject
                    ? WorkshopRegistrationException::subjectScheduleOverlap()
                    : WorkshopRegistrationException::scheduleOverlap();
            }
        }
    }
}
