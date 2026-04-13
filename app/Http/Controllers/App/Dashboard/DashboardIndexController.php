<?php

namespace App\Http\Controllers\App\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\WorkshopRegistration;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        assert($user !== null);

        $base = WorkshopRegistration::query()->where('user_id', $user->id);

        return Inertia::render('app/dashboard/Index', [
            'registrationSummary' => [
                'confirmed' => (clone $base)->confirmed()->count(),
                'waiting_list' => (clone $base)->waitingList()->count(),
            ],
        ]);
    }
}
