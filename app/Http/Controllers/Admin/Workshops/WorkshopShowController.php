<?php

namespace App\Http\Controllers\Admin\Workshops;

use App\Http\Controllers\Controller;
use App\Models\Support\Tables\Workshop\WorkshopRegistrationTableColumns;
use App\Models\Workshop;
use App\Support\Workshop\AdminWorkshopShowState;
use Inertia\Inertia;
use Inertia\Response;

class WorkshopShowController extends Controller
{
    public function __invoke(Workshop $workshop, AdminWorkshopShowState $adminWorkshopShowState): Response
    {
        $state = $adminWorkshopShowState->resolve($workshop);

        return Inertia::render('admin/workshops/Show', [
            'workshop' => $state['workshop'],
            'canAttachParticipants' => $state['canAttachParticipants'],
            'participantList' => $state['participantList'],
            'assignableUsers' => $state['assignableUsers'],
            'participantTableColumns' => WorkshopRegistrationTableColumns::adminShowTable(),
            'filters' => [
                'sort' => null,
                'direction' => null,
            ],
        ]);
    }
}
