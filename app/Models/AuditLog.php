<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(string $event, ?Model $auditable = null, array $data = []): ?self
    {
        try {
            if (!Schema::hasTable('audit_logs')) {
                return null;
            }

            $request = request();

            return self::create([
                'user_id' => $data['user_id'] ?? Auth::id(),
                'event' => $event,
                'auditable_type' => $auditable ? $auditable::class : ($data['auditable_type'] ?? null),
                'auditable_id' => $auditable?->getKey() ?? ($data['auditable_id'] ?? null),
                'ip_address' => $data['ip_address'] ?? $request?->ip(),
                'user_agent' => $data['user_agent'] ?? $request?->userAgent(),
                'method' => $data['method'] ?? $request?->method(),
                'url' => $data['url'] ?? $request?->fullUrl(),
                'description' => $data['description'] ?? null,
                'old_values' => $data['old_values'] ?? null,
                'new_values' => $data['new_values'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
