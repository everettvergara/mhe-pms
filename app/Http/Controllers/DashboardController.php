<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isSupplier()) {
            return view('dashboard.supplier', [
                'data' => $this->dashboardService->forSupplier($user),
            ]);
        }

        return view('dashboard.admin', [
            'data' => $this->dashboardService->forAdmin($user),
        ]);
    }

    public function pmsSchedule(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.pms-schedule', [
            'data' => $this->dashboardService->pmsSchedule($user),
            'isSupplier' => $user->isSupplier(),
        ]);
    }
}
