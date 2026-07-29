<?php

namespace App\Services;

use App\Models\SystemOperation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemOperationService
{
    private const FINISHED_VISIBILITY_HOURS = 24;

    private const QUEUED_STALE_MINUTES = 15;

    private const RUNNING_STALE_MINUTES = 30;

    public function create(
        string $type,
        ?User $user,
        array $payload = [],
        ?string $idempotencyKey = null
    ): SystemOperation {
        if ($idempotencyKey) {
            $existing = SystemOperation::query()
                ->where('user_id', $user?->id)
                ->where('type', $type)
                ->where('idempotency_key', $idempotencyKey)
                ->whereIn('status', ['queued', 'running', 'completed'])
                ->latest()
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return SystemOperation::create([
            'user_id' => $user?->id,
            'type' => $type,
            'status' => 'queued',
            'progress' => 0,
            'message' => 'En espera para iniciar.',
            'payload' => $payload,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function recentFor(?User $user, int $limit = 12)
    {
        $this->expireStaleFor($user);

        return $this->ownedQuery($user)
            ->whereNotIn('status', ['consumed', 'cancelled', 'dismissed'])
            ->where(function (Builder $query) {
                $query->whereIn('status', ['queued', 'running'])
                    ->orWhere('updated_at', '>=', now()->subHours(self::FINISHED_VISIBILITY_HOURS));
            })
            ->latest()
            ->limit(max(1, min($limit, 50)))
            ->get()
            ->map(fn (SystemOperation $operation) => $this->payload($operation, false))
            ->values();
    }

    public function queueConnection(string $workload): string
    {
        $configured = (string) config("queue.workload_connections.{$workload}", 'auto');

        if ($configured !== '' && $configured !== 'auto') {
            return $configured;
        }

        $heartbeat = Cache::get('system:queue-heartbeat');

        try {
            $workerIsActive = $heartbeat
                && abs(now()->diffInMinutes(Carbon::parse($heartbeat))) <= 3;
        } catch (Throwable) {
            $workerIsActive = false;
        }

        return $workerIsActive
            ? (string) config('queue.default', 'database')
            : 'deferred';
    }

    public function dismiss(SystemOperation $operation): void
    {
        if (in_array($operation->status, ['queued', 'running'], true)) {
            $operation->markCancelled('Operación cancelada por el usuario.');
            $this->cleanupSourceFile($operation);
            return;
        }

        $operation->dismiss();
    }

    public function dismissFinishedFor(?User $user): int
    {
        $operations = $this->ownedQuery($user)
            ->whereNotIn('status', ['queued', 'running', 'dismissed'])
            ->get();

        foreach ($operations as $operation) {
            $operation->dismiss();
        }

        return $operations->count();
    }

    public function payload(SystemOperation $operation, bool $includeResult = true): array
    {
        $payload = [
            'id' => $operation->id,
            'type' => $operation->type,
            'status' => $operation->status,
            'progress' => (int) $operation->progress,
            'message' => $operation->message,
            'download_name' => $operation->download_name,
            'download_url' => $operation->status === 'completed' && $operation->result_path
                ? route('operaciones.descargar', $operation)
                : null,
            'error' => $operation->status === 'failed' ? $operation->error : null,
            'created_at' => $operation->created_at?->toISOString(),
            'updated_at' => $operation->updated_at?->toISOString(),
            'finished_at' => $operation->finished_at?->toISOString(),
        ];

        if ($includeResult) {
            $payload['result'] = $operation->result;
        }

        return $payload;
    }

    private function expireStaleFor(?User $user): void
    {
        $queued = $this->ownedQuery($user)
            ->where('status', 'queued')
            ->where('updated_at', '<', now()->subMinutes(self::QUEUED_STALE_MINUTES))
            ->get();

        foreach ($queued as $operation) {
            $operation->markCancelled('La tarea no inició porque no había un procesador disponible. Vuelve a intentarlo.');
            $this->cleanupSourceFile($operation);
        }

        $running = $this->ownedQuery($user)
            ->where('status', 'running')
            ->where('updated_at', '<', now()->subMinutes(self::RUNNING_STALE_MINUTES))
            ->get();

        foreach ($running as $operation) {
            $operation->markCancelled('La tarea dejó de responder y fue cerrada automáticamente. Vuelve a intentarlo.');
            $this->cleanupSourceFile($operation);
        }
    }

    private function ownedQuery(?User $user): Builder
    {
        return SystemOperation::query()
            ->when(
                $user,
                fn (Builder $query) => $query->where('user_id', $user->id),
                fn (Builder $query) => $query->whereNull('user_id')
            );
    }

    private function cleanupSourceFile(SystemOperation $operation): void
    {
        if ($operation->type !== 'attendance_import_preview') {
            return;
        }

        $path = (string) data_get($operation->payload, 'stored_path', '');

        if ($path !== '' && str_starts_with($path, 'imports/reloj/')) {
            Storage::disk('local')->delete($path);
        }
    }
}
