<?php

namespace App\Http\Controllers\App\Workshops;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workshops\ListWorkshopsIndexRequest;
use App\Http\Resources\Workshop\WorkshopListItemResource;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Support\Filters\Workshops\WorkshopUserFilters;
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

        $workshopList = $workshops->map(function (Workshop $workshop) use ($request, $registrationByWorkshopId) {
            $status = $registrationByWorkshopId->get($workshop->id)?->status;

            return (new WorkshopListItemResource($workshop, $status))->resolve($request);
        })->all();

        return Inertia::render('app/workshops/Index', [
            'workshopList' => $workshopList,
            'filters' => $data['filters'],
            'cardFilterFields' => $data['cardFilterFields'],
        ]);
    }
}
