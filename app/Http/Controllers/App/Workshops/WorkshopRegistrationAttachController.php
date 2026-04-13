<?php

namespace App\Http\Controllers\App\Workshops;

use App\Exceptions\Workshop\WorkshopRegistrationException;
use App\Http\Controllers\Controller;
use App\Models\Workshop;
use App\Services\Workshop\WorkshopRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class WorkshopRegistrationAttachController extends Controller
{
    public function __construct(
        private WorkshopRegistrationService $workshopRegistrationService,
    ) {}

    public function __invoke(Request $request, Workshop $workshop): RedirectResponse
    {
        Gate::authorize('attachRegistration', $workshop);

        $user = $request->user();
        assert($user !== null);

        try {
            $this->workshopRegistrationService->attach($user, $workshop);
        } catch (WorkshopRegistrationException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('You are registered for this workshop.'),
        ]);

        return back();
    }
}
