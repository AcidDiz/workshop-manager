<?php

namespace App\Http\Controllers\App\Workshops;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workshops\ListWorkshopsIndexRequest;
use App\Http\Resources\Workshop\WorkshopListItemResource;
use App\Models\Support\Filters\Workshops\WorkshopUserFilters;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Support\Workshop\WorkshopWaitingListPositions;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkshopIndexController extends Controller
{
    public function __invoke(ListWorkshopsIndexRequest $request, WorkshopUserFilters $workshopUserFilters): Response|RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        if ($user->can('workshops.manage')) {
            return redirect()->route('admin.workshops.index', $request->query());
        }

        $data = $workshopUserFilters->index($request->validated());

        $workshops = $data['workshops'];
        $workshopIds = $workshops->pluck('id');
        $registrationByWorkshopId = collect();
        if ($workshopIds->isNotEmpty()) {
            $registrationByWorkshopId = WorkshopRegistration::query()
                ->where('user_id', $user->id)
                ->whereIn('workshop_id', $workshopIds)
                ->get()
                ->keyBy('workshop_id');
        }
        $waitingListPositions = WorkshopWaitingListPositions::forUserRegistrations($registrationByWorkshopId->values());

        $workshopList = $workshops->map(function (Workshop $workshop) use ($request, $registrationByWorkshopId, $waitingListPositions) {
            $resource = new WorkshopListItemResource($workshop);
            $resource->myRegistrationStatus = $registrationByWorkshopId->get($workshop->id)?->status;
            $resource->myWaitingListPosition = $waitingListPositions[$workshop->id] ?? null;

            return $resource->resolve($request);
        })->all();

        return Inertia::render('app/workshops/Index', [
            'workshopList' => $workshopList,
            'filters' => $data['filters'],
            'cardFilterFields' => $data['cardFilterFields'],
        ]);
    }
}
