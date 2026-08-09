<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardOverviewRequest;
use App\Support\Admin\AdminDashboardMetrics;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(DashboardOverviewRequest $request, AdminDashboardMetrics $metrics): View
    {
        return view('admin.dashboard', $metrics->overview($request->filters()));
    }
}
