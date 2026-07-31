<?php

namespace App\Services;

use App\Models\AnnualArchive;
use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\Nomina;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AnnualArchiveService
{
    public function create(int $year): AnnualArchive
    {
        if ($year < 2020 || $year > (int) now()->year) {
            throw new RuntimeException('El año solicitado no es válido para archivar.');
        }

        $summary = [
            'year' => $year,
            'employees_with_activity' => Asistencia::whereYear('fecha', $year)->distinct()->count('empleado_id'),
            'attendance' => Asistencia::whereYear('fecha', $year)->count(),
            'absences' => Asistencia::whereYear('fecha', $year)->where('tipo_asistencia', 'Falta')->count(),
            'payrolls' => Nomina::whereYear('fecha_fin', $year)->count(),
            'paid_payrolls' => Nomina::whereYear('fecha_fin', $year)->where('pagado', true)->count(),
            'net_total' => round((float) Nomina::whereYear('fecha_fin', $year)->sum('pago_neto'), 2),
            'active_employees_at_generation' => Empleado::where('estatus', true)->count(),
            'generated_at' => now()->toISOString(),
            'database' => DB::getDatabaseName(),
        ];
        $encoded = json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

        return AnnualArchive::updateOrCreate(['year' => $year], [
            'status' => 'verified',
            'summary' => $summary,
            'checksum' => hash('sha256', $encoded),
            'verified_at' => now(),
        ]);
    }
}
