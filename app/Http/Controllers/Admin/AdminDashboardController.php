<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Workshop\WorkshopStatisticsService;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct(
        private WorkshopStatisticsService $workshopStatisticsService,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('admin/dashboard/Index', [
            'statistics' => $this->workshopStatisticsService->snapshot(),
        ]);
    }
}
