<?php

namespace App\Http\Controllers;

use App\Models\SystemOperation;
use App\Services\SystemOperationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemOperationController extends Controller
{
    public function index(Request $request, SystemOperationService $service): JsonResponse
    {
        return response()->json([
            'operations' => $service->recentFor($request->user(), (int) $request->input('limit', 12)),
        ]);
    }

    public function show(Request $request, SystemOperation $operation, SystemOperationService $service): JsonResponse
    {
        $this->authorizeOperation($request, $operation);

        return response()->json($service->payload($operation->fresh()));
    }

    public function dismiss(Request $request, SystemOperation $operation, SystemOperationService $service): JsonResponse
    {
        $this->authorizeOperation($request, $operation);
        $service->dismiss($operation);

        return response()->json(['dismissed' => true]);
    }

    public function dismissAll(Request $request, SystemOperationService $service): JsonResponse
    {
        return response()->json([
            'dismissed' => $service->dismissFinishedFor($request->user()),
        ]);
    }

    public function download(Request $request, SystemOperation $operation): StreamedResponse
    {
        $this->authorizeOperation($request, $operation);
        abort_unless($operation->status === 'completed' && $operation->result_path, 404);
        abort_unless(Storage::disk('local')->exists($operation->result_path), 404);

        return Storage::disk('local')->download(
            $operation->result_path,
            $operation->download_name ?: basename($operation->result_path)
        );
    }

    private function authorizeOperation(Request $request, SystemOperation $operation): void
    {
        abort_unless(
            $request->user()?->isAdmin() || $operation->user_id === $request->user()?->id,
            403
        );
    }
}
