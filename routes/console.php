<?php

use App\Models\Empleado;
use App\Models\Nomina;
use App\Services\FirebaseSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('firebase:sync-paid-nominas {--empleado_id=} {--nomina_id=}', function () {
    $query = Nomina::with('empleado')->where('pagado', true);

    if ($this->option('empleado_id')) {
        $query->where('empleado_id', $this->option('empleado_id'));
    }

    if ($this->option('nomina_id')) {
        $query->whereKey($this->option('nomina_id'));
    }

    $total = 0;
    $query->orderBy('id')->chunkById(50, function ($nominas) use (&$total) {
        foreach ($nominas as $nomina) {
            if (!$nomina->empleado) {
                $this->warn("Nomina {$nomina->id} sin empleado, se omitio.");
                continue;
            }

            FirebaseSyncService::sincronizarNominaPagada($nomina->empleado, $nomina, [
                'total_percepciones' => $nomina->total_percepciones,
                'total_deducciones' => $nomina->total_deducciones,
                'pago_neto' => $nomina->pago_neto,
            ]);

            $total++;
        }
    });

    $this->info("Nominas pagadas enviadas a Firebase: {$total}");
})->purpose('Sincroniza con Firebase las nominas que ya estan marcadas como pagadas.');

Artisan::command('firebase:sync-mobile {--empleado_id=} {--incluir-bajas}', function () {
    $query = Empleado::query()
        ->when(!$this->option('incluir-bajas'), fn ($query) => $query->where('estatus', true))
        ->when($this->option('empleado_id'), fn ($query) => $query->whereKey($this->option('empleado_id')))
        ->orderBy('id');

    $total = 0;

    $query->chunkById(25, function ($empleados) use (&$total) {
        foreach ($empleados as $empleado) {
            FirebaseSyncService::sincronizarEmpleadoCompleto($empleado);
            $total++;
            $this->line("Sincronizado empleado {$empleado->id} - {$empleado->nombre_completo}");
        }
    });

    $this->info("Empleados preparados para app movil: {$total}");
})->purpose('Sincroniza perfil, resumen, asistencias y nominas pagadas para la app movil.');

Artisan::command('firebase:link-user {uid} {empleado}', function (string $uid, string $empleado) {
    $empleadoModel = Empleado::where('numero_empleado', $empleado)
        ->orWhere('numero_empleado_baja', $empleado)
        ->first();

    if (!$empleadoModel && is_numeric($empleado)) {
        $empleadoModel = Empleado::find($empleado);
    }

    if (!$empleadoModel) {
        $this->error("No encontre empleado con numero o ID: {$empleado}");
        return 1;
    }

    FirebaseSyncService::vincularUsuarioMobile($uid, $empleadoModel);

    $this->info("Usuario Firebase {$uid} vinculado con empleado {$empleadoModel->id} - {$empleadoModel->nombre_completo}");
})->purpose('Vincula un UID de Firebase Auth con un empleado para que la app solo lea su informacion.');
