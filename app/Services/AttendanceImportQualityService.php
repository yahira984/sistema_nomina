<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AttendanceImportQualityService
{
    public function summarize(array $rows): array
    {
        $rows = collect($rows);
        $incomplete = $rows->where('estado', 'incompleta')->count();
        $unmatched = $rows->where('estado', 'no_encontrado')->count();
        $missing = $rows->where('estado', 'sin_registro')->count();
        $duplicateMarks = $rows->filter(fn ($row) => (int) ($row['marcas'] ?? 0) > 2)->count();
        $invalidTimes = $rows->filter(fn ($row) => $this->invalidTimeOrder($row))->count();
        $observations = $incomplete + $unmatched + $duplicateMarks + $invalidTimes;
        $base = max(1, $rows->count() - $missing);
        $score = max(0, (int) round(100 - ($observations / $base * 100)));

        return [
            'score' => $score,
            'status' => $score >= 95 ? 'good' : ($score >= 80 ? 'warning' : 'critical'),
            'incomplete' => $incomplete,
            'unmatched' => $unmatched,
            'missing' => $missing,
            'duplicate_marks' => $duplicateMarks,
            'invalid_times' => $invalidTimes,
            'requires_attention' => $observations,
        ];
    }

    private function invalidTimeOrder(array $row): bool
    {
        if (($row['tipo_asistencia'] ?? '') !== 'Normal' || empty($row['hora_entrada']) || empty($row['hora_salida'])) {
            return false;
        }

        return (float) ($row['horas_trabajadas'] ?? 0) <= 0;
    }
}
