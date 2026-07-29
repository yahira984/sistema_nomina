<?php

namespace App\Http\Controllers;

use App\Models\IntegrationFailure;
use App\Models\SystemBackup;
use App\Services\SystemHealthService;
use App\Services\SystemOperationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemHealthController extends Controller
{
    public function index(
        Request $request,
        SystemHealthService $health,
        SystemOperationService $operations
    ) {
        return Inertia::render('Sistema/Salud', [
            'health' => $health->snapshot(),
            'backups' => SystemBackup::latest()->limit(10)->get(),
            'failures' => IntegrationFailure::latest()->limit(25)->get(),
            'operations' => $operations->recentFor($request->user(), 15),
        ]);
    }
}
