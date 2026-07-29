<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Throwable;

class SystemOperation extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'progress' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $operation) {
            $operation->id ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markRunning(string $message = 'Procesando...'): self
    {
        $this->forceFill([
            'status' => 'running',
            'progress' => max(1, $this->progress),
            'message' => $message,
            'started_at' => $this->started_at ?: now(),
        ])->save();

        return $this;
    }

    public function reportProgress(int $progress, string $message): self
    {
        $this->forceFill([
            'status' => 'running',
            'progress' => min(99, max(1, $progress)),
            'message' => $message,
            'started_at' => $this->started_at ?: now(),
        ])->save();

        return $this;
    }

    public function markCompleted(
        string $message,
        array $result = [],
        ?string $resultPath = null,
        ?string $downloadName = null
    ): self {
        $this->forceFill([
            'status' => 'completed',
            'progress' => 100,
            'message' => $message,
            'result' => $result,
            'result_path' => $resultPath,
            'download_name' => $downloadName,
            'error' => null,
            'finished_at' => now(),
        ])->save();

        return $this;
    }

    public function markFailed(Throwable|string $error, ?string $message = null): self
    {
        $errorMessage = $error instanceof Throwable ? $error->getMessage() : $error;

        $this->forceFill([
            'status' => 'failed',
            'message' => $message ?: 'No fue posible completar la operación.',
            'error' => $errorMessage,
            'finished_at' => now(),
        ])->save();

        return $this;
    }

    public function markCancelled(string $message = 'Operación cancelada.'): self
    {
        $this->forceFill([
            'status' => 'cancelled',
            'message' => $message,
            'finished_at' => now(),
        ])->save();

        return $this;
    }

    public function dismiss(): self
    {
        $this->forceFill([
            'status' => 'dismissed',
            'finished_at' => $this->finished_at ?: now(),
        ])->save();

        return $this;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled', 'consumed', 'dismissed'], true);
    }
}
