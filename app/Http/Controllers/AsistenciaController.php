<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AsistenciaController extends Controller
{
    private const PREVIEW_SESSION_KEY = 'asistencias.importacion_preview';

    public function index(Request $request)
    {
        $empleados = Empleado::where('estatus', true)->orderBy('nombre_completo', 'asc')->get();
        $asistencias = Asistencia::with('empleado')->orderBy('fecha', 'desc')->get();

        return Inertia::render('Asistencias/Index', [
            'empleados' => $empleados,
            'asistencias' => $asistencias,
            'previewImportacion' => $request->session()->get(self::PREVIEW_SESSION_KEY),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'fecha' => 'required|date',
            'tipo_asistencia' => 'required|string|in:Normal,Falta,Incapacidad,Vacaciones',
        ]);

        $datosCalculados = $this->calcularHoras($request->fecha, $request->hora_entrada, $request->hora_salida, $request->tipo_asistencia);

        Asistencia::create(array_merge([
            'empleado_id' => $request->empleado_id,
            'fecha' => $request->fecha,
            'tipo_asistencia' => $request->tipo_asistencia,
            'hora_entrada' => $request->tipo_asistencia === 'Normal' ? $request->hora_entrada : null,
            'hora_salida' => $request->tipo_asistencia === 'Normal' ? $request->hora_salida : null,
        ], $datosCalculados));

        return redirect()->back()->with('success', 'Asistencia registrada con exito.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'fecha' => 'required|date',
            'tipo_asistencia' => 'required|string|in:Normal,Falta,Incapacidad,Vacaciones',
        ]);

        $asistencia = Asistencia::findOrFail($id);
        $datosCalculados = $this->calcularHoras($request->fecha, $request->hora_entrada, $request->hora_salida, $request->tipo_asistencia);

        $asistencia->update(array_merge([
            'fecha' => $request->fecha,
            'tipo_asistencia' => $request->tipo_asistencia,
            'hora_entrada' => $request->tipo_asistencia === 'Normal' ? $request->hora_entrada : null,
            'hora_salida' => $request->tipo_asistencia === 'Normal' ? $request->hora_salida : null,
        ], $datosCalculados));

        return redirect()->back()->with('success', 'Asistencia actualizada.');
    }

    public function destroy($id)
    {
        $asistencia = Asistencia::findOrFail($id);
        $asistencia->delete();

        return redirect()->back()->with('success', 'Asistencia eliminada.');
    }

    public function importarReloj(Request $request)
    {
        $request->validate([
            'archivo_reloj' => 'required|file|mimes:csv,txt',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
            $fin = Carbon::parse($request->fecha_fin)->startOfDay();

            if ($fin->lt($inicio)) {
                return redirect()->back()->withErrors([
                    'fecha_fin' => 'La fecha final no puede ser menor a la fecha inicial.',
                ]);
            }
        }

        $preview = $this->prepararRevisionImportacion(
            $request->file('archivo_reloj')->getRealPath(),
            $request->fecha_inicio,
            $request->fecha_fin
        );

        $request->session()->put(self::PREVIEW_SESSION_KEY, $preview);

        return redirect()->back()->with('success', 'Archivo analizado. Revisa y aprueba las asistencias detectadas.');
    }

    public function aprobarImportacion(Request $request)
    {
        $validated = $request->validate([
            'filas' => 'required|array|min:1',
            'filas.*.aprobado' => 'nullable|boolean',
            'filas.*.empleado_id' => 'nullable|exists:empleados,id',
            'filas.*.fecha' => 'required|date',
            'filas.*.tipo_asistencia' => 'required|string|in:Normal,Falta,Incapacidad,Vacaciones',
            'filas.*.hora_entrada' => 'nullable|date_format:H:i',
            'filas.*.hora_salida' => 'nullable|date_format:H:i',
        ]);

        $guardadas = 0;
        $omitidas = 0;

        foreach ($validated['filas'] as $fila) {
            if (!($fila['aprobado'] ?? false) || empty($fila['empleado_id'])) {
                $omitidas++;
                continue;
            }

            $tipoAsistencia = $fila['tipo_asistencia'];
            $horaEntrada = $tipoAsistencia === 'Normal' ? ($fila['hora_entrada'] ?? null) : null;
            $horaSalida = $tipoAsistencia === 'Normal' ? ($fila['hora_salida'] ?? null) : null;

            $datosCalculados = $this->calcularHoras($fila['fecha'], $horaEntrada, $horaSalida, $tipoAsistencia);

            Asistencia::updateOrCreate(
                [
                    'empleado_id' => $fila['empleado_id'],
                    'fecha' => $fila['fecha'],
                ],
                array_merge([
                    'tipo_asistencia' => $tipoAsistencia,
                    'hora_entrada' => $horaEntrada,
                    'hora_salida' => $horaSalida,
                ], $datosCalculados)
            );

            $guardadas++;
        }

        if ($guardadas > 0) {
            $request->session()->forget(self::PREVIEW_SESSION_KEY);
        }

        return redirect()->back()->with('success', "Importacion aprobada: {$guardadas} registro(s) guardado(s), {$omitidas} omitido(s).");
    }

    public function descartarImportacion(Request $request)
    {
        $request->session()->forget(self::PREVIEW_SESSION_KEY);

        return redirect()->back()->with('success', 'Revision de importacion descartada.');
    }

    private function prepararRevisionImportacion(string $path, ?string $fechaInicio, ?string $fechaFin): array
    {
        [$agrupados, $fechasCsv] = $this->leerMarcajesCsv($path);

        $rango = $this->resolverRangoRevision($fechasCsv, $fechaInicio, $fechaFin);
        $empleados = Empleado::where('estatus', true)->orderBy('nombre_completo', 'asc')->get();
        $empleadosPorNumero = $this->indexarEmpleadosPorNumero($empleados);
        $existentes = $this->obtenerAsistenciasExistentes($empleados->pluck('id')->all(), $rango);

        $filas = [];
        $detectadasCsv = 0;
        $noEncontradas = 0;
        $clavesConCsv = [];

        foreach ($agrupados as $numeroEmpleado => $fechas) {
            $empleado = $empleadosPorNumero[$numeroEmpleado]
                ?? $empleadosPorNumero[$this->normalizarNumeroEmpleado($numeroEmpleado)]
                ?? null;

            foreach ($fechas as $fecha => $horas) {
                if ($rango && !$this->fechaDentroDeRango($fecha, $rango)) {
                    continue;
                }

                sort($horas);
                $horaEntrada = $horas[0] ?? null;
                $horaSalida = count($horas) > 1 ? end($horas) : null;

                // 🔥 REGLA PROMATEC / LUGARTH: EL "MIÉRCOLES FANTASMA" 🔥
                // Si el día es miércoles y no hay salida registrada (porque el corte se hace a las 5:00 pm),
                // inyectamos automáticamente la salida a las 17:30 para cerrar nómina.
                // La siguiente semana, si el CSV trae la hora real de ese mismo miércoles, sobrescribirá este valor.
                if (Carbon::parse($fecha)->isWednesday() && !$horaSalida) {
                    $horaSalida = '17:30'; 
                }

                $datosCalculados = $this->calcularHoras($fecha, $horaEntrada, $horaSalida, 'Normal');

                if (!$empleado) {
                    $noEncontradas++;
                    $filas[] = array_merge([
                        'aprobado' => false,
                        'empleado_id' => null,
                        'numero_empleado' => $numeroEmpleado,
                        'nombre_completo' => 'Empleado no encontrado',
                        'fecha' => $fecha,
                        'tipo_asistencia' => 'Normal',
                        'hora_entrada' => $horaEntrada,
                        'hora_salida' => $horaSalida,
                        'estado' => 'no_encontrado',
                        'mensaje' => 'El numero del CSV no existe en empleados activos. Selecciona un empleado o deja la fila sin aprobar.',
                        'marcas' => count($horas),
                    ], $datosCalculados);
                    continue;
                }

                $clave = $this->claveAsistencia($empleado->id, $fecha);
                $clavesConCsv[$clave] = true;
                $yaExiste = isset($existentes[$clave]);
                $detectadasCsv++;

                $filas[] = array_merge([
                    'aprobado' => true,
                    'empleado_id' => $empleado->id,
                    'numero_empleado' => $empleado->numero_empleado,
                    'nombre_completo' => $empleado->nombre_completo,
                    'fecha' => $fecha,
                    'tipo_asistencia' => 'Normal',
                    'hora_entrada' => $horaEntrada,
                    'hora_salida' => $horaSalida,
                    'estado' => $yaExiste ? 'actualiza' : 'detectada',
                    'mensaje' => $yaExiste
                        ? 'Ya habia una asistencia para este dia; al aprobar se actualizara.'
                        : (count($horas) === 1 && !Carbon::parse($fecha)->isWednesday() ? 'Solo se detecto una marca del reloj.' : 'Marcajes detectados en CSV.'),
                    'marcas' => count($horas),
                ], $datosCalculados);
            }
        }

        $sinRegistro = 0;

        if ($rango) {
            foreach ($empleados as $empleado) {
                foreach ($this->diasLaborales($rango['inicio'], $rango['fin']) as $fecha) {
                    $clave = $this->claveAsistencia($empleado->id, $fecha);

                    if (isset($clavesConCsv[$clave]) || isset($existentes[$clave])) {
                        continue;
                    }

                    $sinRegistro++;
                    $filas[] = [
                        'aprobado' => true,
                        'empleado_id' => $empleado->id,
                        'numero_empleado' => $empleado->numero_empleado,
                        'nombre_completo' => $empleado->nombre_completo,
                        'fecha' => $fecha,
                        'tipo_asistencia' => 'Falta',
                        'hora_entrada' => null,
                        'hora_salida' => null,
                        'minutos_tarde' => 0,
                        'horas_trabajadas' => 0,
                        'horas_extra' => 0,
                        'estado' => 'sin_registro',
                        'mensaje' => 'No hay marcajes en el CSV ni asistencia capturada para este dia laboral.',
                        'marcas' => 0,
                    ];
                }
            }
        }

        usort($filas, function ($a, $b) {
            return [$a['fecha'], $a['nombre_completo'], $a['estado']] <=> [$b['fecha'], $b['nombre_completo'], $b['estado']];
        });

        return [
            'filas' => $filas,
            'resumen' => [
                'detectadas_csv' => $detectadasCsv,
                'sin_registro' => $sinRegistro,
                'no_encontradas' => $noEncontradas,
                'total' => count($filas),
                'fecha_inicio' => $rango ? $rango['inicio']->format('Y-m-d') : null,
                'fecha_fin' => $rango ? $rango['fin']->format('Y-m-d') : null,
            ],
        ];
    }

    private function leerMarcajesCsv(string $path): array
    {
        $file = fopen($path, 'r');
        $agrupados = [];
        $fechas = [];

        while (($fila = fgetcsv($file, 0, ',')) !== false) {
            $fila = $this->normalizarFilaCsv($fila);

            if (count($fila) < 4) {
                continue;
            }

            $numeroEmpleado = $this->limpiarNumeroEmpleado($fila[0] ?? '');
            $fechaReloj = trim((string) ($fila[2] ?? ''));
            $horaReloj = trim((string) ($fila[3] ?? ''));

            if ($numeroEmpleado === '' || $fechaReloj === '' || $horaReloj === '') {
                continue;
            }

            $fecha = $this->parseFechaCsv($fechaReloj);
            $hora = $this->parseHoraCsv($horaReloj);

            if (!$fecha || !$hora) {
                continue;
            }

            $fechaFormateada = $fecha->format('Y-m-d');
            $agrupados[$numeroEmpleado][$fechaFormateada][] = $hora;
            $fechas[] = $fechaFormateada;
        }

        fclose($file);

        return [$agrupados, array_values(array_unique($fechas))];
    }

    private function normalizarFilaCsv(array $fila): array
    {
        if (count($fila) > 1) {
            return $fila;
        }

        foreach ([';', "\t"] as $separador) {
            $separada = str_getcsv($fila[0] ?? '', $separador);

            if (count($separada) > 1) {
                return $separada;
            }
        }

        return $fila;
    }

    private function limpiarNumeroEmpleado(string $numero): string
    {
        return trim(preg_replace('/^\xEF\xBB\xBF/', '', $numero));
    }

    private function normalizarNumeroEmpleado(string $numero): string
    {
        $normalizado = ltrim($this->limpiarNumeroEmpleado($numero), '0');

        return $normalizado === '' ? '0' : $normalizado;
    }

    private function indexarEmpleadosPorNumero($empleados): array
    {
        $indexados = [];

        foreach ($empleados as $empleado) {
            if (!$empleado->numero_empleado) {
                continue;
            }

            $numero = $this->limpiarNumeroEmpleado((string) $empleado->numero_empleado);
            $indexados[$numero] = $empleado;
            $indexados[$this->normalizarNumeroEmpleado($numero)] = $empleado;
        }

        return $indexados;
    }

    private function resolverRangoRevision(array $fechasCsv, ?string $fechaInicio, ?string $fechaFin): ?array
    {
        $inicio = $fechaInicio ? Carbon::parse($fechaInicio)->startOfDay() : null;
        $fin = $fechaFin ? Carbon::parse($fechaFin)->startOfDay() : null;

        if (!$inicio && count($fechasCsv) > 0) {
            $inicio = Carbon::parse(min($fechasCsv))->startOfDay();
        }

        if (!$fin && count($fechasCsv) > 0) {
            $fin = Carbon::parse(max($fechasCsv))->startOfDay();
        }

        if ($inicio && !$fin) {
            $fin = $inicio->copy();
        }

        if ($fin && !$inicio) {
            $inicio = $fin->copy();
        }

        if (!$inicio || !$fin) {
            return null;
        }

        if ($fin->lt($inicio)) {
            [$inicio, $fin] = [$fin, $inicio];
        }

        return [
            'inicio' => $inicio,
            'fin' => $fin,
        ];
    }

    private function obtenerAsistenciasExistentes(array $empleadoIds, ?array $rango): array
    {
        if (!$rango || count($empleadoIds) === 0) {
            return [];
        }

        return Asistencia::whereIn('empleado_id', $empleadoIds)
            ->whereBetween('fecha', [
                $rango['inicio']->format('Y-m-d'),
                $rango['fin']->format('Y-m-d'),
            ])
            ->get()
            ->keyBy(fn ($asistencia) => $this->claveAsistencia($asistencia->empleado_id, $asistencia->fecha))
            ->all();
    }

    private function fechaDentroDeRango(string $fecha, array $rango): bool
    {
        $fechaCarbon = Carbon::parse($fecha)->startOfDay();

        return $fechaCarbon->betweenIncluded($rango['inicio'], $rango['fin']);
    }

    private function diasLaborales(Carbon $inicio, Carbon $fin): array
    {
        $dias = [];
        $cursor = $inicio->copy()->startOfDay();
        $limite = $fin->copy()->startOfDay();

        while ($cursor->lte($limite)) {
            if (!$cursor->isSunday()) {
                $dias[] = $cursor->format('Y-m-d');
            }

            $cursor->addDay();
        }

        return $dias;
    }

    private function claveAsistencia($empleadoId, string $fecha): string
    {
        return $empleadoId . '|' . $fecha;
    }

    private function parseFechaCsv(?string $fecha): ?Carbon
    {
        $fecha = trim((string) $fecha);

        if ($fecha === '') {
            return null;
        }

        foreach (['m/d/Y', 'n/j/Y', 'Y-m-d', 'd/m/Y', 'j/n/Y', 'm-d-Y', 'd-m-Y'] as $formato) {
            try {
                return Carbon::createFromFormat('!' . $formato, $fecha);
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($fecha);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseHoraCsv(?string $hora): ?string
    {
        $hora = strtoupper(trim((string) $hora));
        $hora = str_replace('.', '', $hora);

        if ($hora === '') {
            return null;
        }

        foreach (['H:i:s', 'H:i', 'G:i:s', 'G:i', 'h:i:s A', 'h:i A', 'g:i:s A', 'g:i A'] as $formato) {
            try {
                return Carbon::createFromFormat('!' . $formato, $hora)->format('H:i');
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($hora)->format('H:i');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function calcularHoras($fecha, $hora_entrada, $hora_salida, $tipo_asistencia)
    {
        $minutos_tarde = 0;
        $horas_normales = 0;
        $horas_extra_diarias = 0;

        if ($tipo_asistencia === 'Normal' && $hora_entrada && $hora_salida) {
            $fecha_carbon = Carbon::parse($fecha);
            $entrada = Carbon::parse($fecha . ' ' . $hora_entrada);
            $salida = Carbon::parse($fecha . ' ' . $hora_salida);
            $hora_oficial = Carbon::parse($fecha . ' 08:00:00');

            if ($entrada->greaterThan($hora_oficial)) {
                $minutos_tarde = $hora_oficial->diffInMinutes($entrada);
            }

            if ($fecha_carbon->isSaturday()) {
                $horas_extra_diarias = $entrada->diffInMinutes($salida) / 60;
            } else {
                $limite_normal = Carbon::parse($fecha . ' 17:30:00');
                if ($salida->greaterThan($limite_normal)) {
                    $horas_normales = $entrada->diffInMinutes($limite_normal) / 60;
                    $horas_extra_diarias = $limite_normal->diffInMinutes($salida) / 60;
                } else {
                    $horas_normales = $entrada->diffInMinutes($salida) / 60;
                }
            }
        }

        return [
            'minutos_tarde' => $minutos_tarde,
            'horas_trabajadas' => $horas_normales,
            'horas_extra' => $horas_extra_diarias,
        ];
    }
}