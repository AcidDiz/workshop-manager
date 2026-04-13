<?php

namespace App\Http\Controllers\App\Workshops;

use App\Http\Controllers\Controller;
use App\Models\Workshop;
use App\Services\Workshop\WorkshopCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class WorkshopRegistrationDetachController extends Controller
{
    public function __construct(
        private WorkshopCancellationService $workshopCancellationService,
    ) {}

    public function __invoke(Request $request, Workshop $workshop): RedirectResponse
    {
        Gate::authorize('detachRegistration', $workshop);

        $user = $request->user();
        assert($user !== null);

        $removed = $this->workshopCancellationService->detach($user, $workshop);

        Inertia::flash('toast', [
            'type' => $removed ? 'success' : 'info',
            'message' => $removed
                ? __('Your registration has been cancelled.')
                : __('You were not registered for this workshop.'),
        ]);

        return back();
    }
}
