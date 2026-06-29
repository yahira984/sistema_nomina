<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuditObserver
{
    private array $ignored = [
        'created_at',
        'updated_at',
        'remember_token',
        'password',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
    ];

    public function created(Model $model): void
    {
        AuditLog::record($this->event($model, 'created'), $model, [
            'description' => $this->description($model, 'creado'),
            'new_values' => $this->clean($model->getAttributes()),
        ]);
    }

    public function updated(Model $model): void
    {
        $changes = $this->clean($model->getChanges());

        if (empty($changes)) {
            return;
        }

        AuditLog::record($this->event($model, 'updated'), $model, [
            'description' => $this->description($model, 'actualizado'),
            'old_values' => $this->clean(Arr::only($model->getOriginal(), array_keys($changes))),
            'new_values' => $changes,
        ]);
    }

    public function deleted(Model $model): void
    {
        AuditLog::record($this->event($model, 'deleted'), $model, [
            'description' => $this->description($model, 'eliminado'),
            'old_values' => $this->clean($model->getOriginal()),
        ]);
    }

    private function event(Model $model, string $action): string
    {
        return Str::of(class_basename($model))->snake()->append(".{$action}")->toString();
    }

    private function description(Model $model, string $action): string
    {
        return class_basename($model) . " {$action} #" . $model->getKey();
    }

    private function clean(array $values): array
    {
        return Arr::except($values, $this->ignored);
    }
}
