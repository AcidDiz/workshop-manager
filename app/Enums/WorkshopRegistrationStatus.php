<?php

namespace App\Enums;

enum WorkshopRegistrationStatus: string
{
    case Confirmed = 'confirmed';
    case WaitingList = 'waiting_list';
}
