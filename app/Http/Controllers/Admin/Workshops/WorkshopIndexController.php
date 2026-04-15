<?php

namespace App\Http\Controllers\Admin\Workshops;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workshops\ListWorkshopsIndexRequest;
use App\Http\Resources\Workshop\WorkshopListItemResource;
use App\Models\Support\Filters\Workshops\WorkshopAdminFilters;
use Inertia\Inertia;
use Inertia\Response;

class WorkshopIndexController extends Controller
{
    public function __invoke(ListWorkshopsIndexRequest $request, WorkshopAdminFilters $workshopAdminFilters): Response
    {
        $user = $request->user();
        assert($user !== null);

        $data = $workshopAdminFilters->index($request->validated());

        return Inertia::render('admin/workshops/Index', [
            'workshopList' => WorkshopListItemResource::collection($data['workshops'])
                ->resolve($request),
            'filters' => $data['filters'],
            'workshopTableColumns' => $data['workshopTableColumns'],
        ]);
    }
}
