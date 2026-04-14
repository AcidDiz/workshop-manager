<?php

namespace App\Exceptions\Workshop;

use DomainException;

class WorkshopRegistrationException extends DomainException
{
    public static function workshopClosed(): self
    {
        return new self(__('This workshop is no longer open for registration.'));
    }

    public static function alreadyRegistered(): self
    {
        return new self(__('You are already registered for this workshop.'));
    }

    public static function scheduleOverlap(): self
    {
        return new self(__('You already have a registration that overlaps this workshop time.'));
    }

    public static function subjectAlreadyRegistered(): self
    {
        return new self(__('This user is already registered for this workshop.'));
    }

    public static function subjectScheduleOverlap(): self
    {
        return new self(__('This user already has another registration overlapping this workshop time.'));
    }

    public static function workshopFullForAdminAttach(): self
    {
        return new self(__(
            'This workshop is at capacity. Remove a participant or increase capacity before adding another.'
        ));
    }
}
