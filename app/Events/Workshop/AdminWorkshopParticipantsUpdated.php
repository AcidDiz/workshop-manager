<?php

namespace App\Events\Workshop;

use App\Broadcasting\PrivateBroadcastEvent;

class AdminWorkshopParticipantsUpdated extends PrivateBroadcastEvent
{
    private const EVENT = 'workshop.participants.updated';

    /**
     * @param  array{
     *     workshop: array<string, mixed>,
     *     canAttachParticipants: bool,
     *     participantList: array<int, array<string, mixed>>,
     *     assignableUsers: array<int, array{id: int, name: string, email: string}>
     * }  $state
     */
    public function __construct(
        public int $workshopId,
        public array $state,
    ) {}

    protected function broadcastChannelName(): string
    {
        return "admin.workshops.{$this->workshopId}";
    }

    protected function broadcastEventName(): string
    {
        return self::EVENT;
    }

    /**
     * @return array<string, mixed>
     */
    protected function broadcastPayload(): array
    {
        return [
            'workshop' => $this->state['workshop'],
            'canAttachParticipants' => $this->state['canAttachParticipants'],
            'participantList' => $this->state['participantList'],
            'assignableUsers' => $this->state['assignableUsers'],
        ];
    }
}
