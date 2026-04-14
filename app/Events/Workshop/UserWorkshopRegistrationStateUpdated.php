<?php

namespace App\Events\Workshop;

use App\Broadcasting\PrivateBroadcastEvent;

class UserWorkshopRegistrationStateUpdated extends PrivateBroadcastEvent
{
    private const EVENT = 'workshops.registration-state.updated';

    public function __construct(
        public int $userId,
        public int $workshopId,
        public ?string $registrationStatus,
        public ?int $waitingListPosition,
    ) {}

    protected function broadcastChannelName(): string
    {
        return "App.Models.User.{$this->userId}";
    }

    protected function broadcastEventName(): string
    {
        return self::EVENT;
    }

    /**
     * @return array<string, int|string|null>
     */
    protected function broadcastPayload(): array
    {
        return [
            'workshop_id' => $this->workshopId,
            'registration_status' => $this->registrationStatus,
            'waiting_list_position' => $this->waitingListPosition,
        ];
    }
}
