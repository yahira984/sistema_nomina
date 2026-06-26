<?php

namespace App\Http\Controllers;

use App\Exports\AsistenciasSemanalesExport;
use App\Models\Asistencia;
use App\Models\Empleado;
use App\Services\FirebaseSyncService;
use App\Support\ReglasNominaEmpleado;
use App\Support\SemanaNomina;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AsistenciaController extends Controller
{
    private const PREVIEW_SESSION_KEY = 'asistencias.importacion_preview';

    public function index(Request $request)
    {
        $empleados = Empleado::where('estatus', true)
            ->orderByRaw("CAST(COALESCE(NULLIF(numero_empleado, ''), NULLIF(numero_empleado_baja, ''), id) AS UNSIGNED) ASC")
            ->orderBy('nombre_completo', 'asc')
            ->get();
        $asistencias = Asistencia::with('empleado')->orderBy('fecha', 'desc')->get();

        return Inertia::render('Asistencias/Index', [
            'empleados' => $empleados,
            'asistencias' => $asistencias,
            'previewImportacion' => $request->session()->get(self::PREVIEW_SESSION_KEY),
        ]);
    }

    public function exportarSemana(Request $request)
    {
        $inicioSemana = $this->inicioSemanaNomina($request->input('fecha', now()->format('Y-m-d')));
        $finSemana = $inicioSemana->copy()->addDays(6);
        $nombreArchivo = 'Asistencias_Semana_' . $inicioSemana->isoWeek()
            . '_' . $inicioSemana->format('Ymd')
            . '_' . $finSemana->format('Ymd')
            . '.xlsx';

        return Excel::download(new AsistenciasSemanalesExport($inicioSemana, $finSemana), $nombreArchivo);
    }

    public function horasAlumnos(Request $request)
    {
        [$inicioSemana, $finSemana, $numeroSemana] = SemanaNomina::desdeCorte(
            $request->input('fecha_corte') ?: SemanaNomina::corteActual()->format('Y-m-d')
        );

        return Inertia::render('Asistencias/HorasAlumnos', [
            'estudiantes' => $this->queryAlumnosActivos()->get(),
            'semanas' => SemanaNomina::disponibles(SemanaNomina::corteActual(), 14),
            'fechaCorteActual' => $finSemana->format('Y-m-d'),
            'numeroSemanaActual' => $numeroSemana,
            'rangoSemanaActual' => $this->rangoSemanaTexto($inicioSemana, $finSemana),
        ]);
    }

    public function imprimirHorasAlumnos(Request $request)
    {
        $request->validate([
            'fecha_corte' => 'nullable|date',
        ]);

        [$inicioSemana, $finSemana, $numeroSemana] = SemanaNomina::desdeCorte(
            $request->input('fecha_corte') ?: SemanaNomina::corteActual()->format('Y-m-d')
        );

        $empleadoIds = $this->resolverIdsSeleccionados($request->input('empleado_ids', []));
        $empleados = $this->queryAlumnosActivos()
            ->when(count($empleadoIds) > 0, fn ($query) => $query->whereIn('id', $empleadoIds))
            ->get();

        $asistenciasPorAlumno = Asistencia::whereIn('empleado_id', $empleados->pluck('id')->all())
            ->whereBetween('fecha', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')])
            ->where('tipo_asistencia', 'Normal')
            ->orderBy('fecha')
            ->get()
            ->groupBy('empleado_id');

        $alumnos = $empleados->map(function (Empleado $empleado) use ($asistenciasPorAlumno) {
            $registros = $this->registrosHorasServicio($asistenciasPorAlumno->get($empleado->id, collect()));

            return [
                'empleado' => $empleado,
                'registros' => $registros,
                'total_horas' => round($registros->sum('horas'), 2),
                'total_horas_texto' => $this->formatoHorasServicio(round($registros->sum('horas'), 2)),
            ];
        })->values();

        $pdf = Pdf::loadView('pdf.horas_servicio_alumnos', [
            'alumnos' => $alumnos,
            'universidad' => '',
            'horasCumplir' => '',
            'inicioSemana' => $inicioSemana,
            'finSemana' => $finSemana,
            'numeroSemana' => $numeroSemana,
            'rangoSemana' => $this->rangoSemanaTexto($inicioSemana, $finSemana),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('Registro_Horas_Alumnos_Semana_' . $numeroSemana . '.pdf');
    }

    public function store(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'fecha' => 'required|date',
            'tipo_asistencia' => 'required|string|in:Normal,Falta,Incapacidad,Vacaciones',
        ]);

        $empleado = Empleado::findOrFail($request->empleado_id);
        $datosCalculados = $this->calcularHoras($request->fecha, $request->hora_entrada, $request->hora_salida, $request->tipo_asistencia, $empleado);

        $asistencia = Asistencia::create(array_merge([
            'empleado_id' => $request->empleado_id,
            'fecha' => $request->fecha,
            'tipo_asistencia' => $request->tipo_asistencia,
            'hora_entrada' => $request->tipo_asistencia === 'Normal' ? $request->hora_entrada : null,
            'hora_salida' => $request->tipo_asistencia === 'Normal' ? $request->hora_salida : null,
        ], $datosCalculados));

        FirebaseSyncService::sincronizarAsistencia($asistencia);

        return redirect()->back()->with('success', 'Asistencia registrada con exito.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'fecha' => 'required|date',
            'tipo_asistencia' => 'required|string|in:Normal,Falta,Incapacidad,Vacaciones',
        ]);

        $asistencia = Asistencia::findOrFail($id);
        $datosCalculados = $this->calcularHoras($request->fecha, $request->hora_entrada, $request->hora_salida, $request->tipo_asistencia, $asistencia->empleado);

        $asistencia->update(array_merge([
            'fecha' => $request->fecha,
            'tipo_asistencia' => $request->tipo_asistencia,
            'hora_entrada' => $request->tipo_asistencia === 'Normal' ? $request->hora_entrada : null,
            'hora_salida' => $request->tipo_asistencia === 'Normal' ? $request->hora_salida : null,
        ], $datosCalculados));

        FirebaseSyncService::sincronizarAsistencia($asistencia->fresh('empleado'));

        return redirect()->back()->with('success', 'Asistencia actualizada.');
    }

    public function destroy($id)
    {
        $asistencia = Asistencia::with('empleado')->findOrFail($id);
        $asistencia->delete();

        FirebaseSyncService::eliminarAsistencia($asistencia);

        return redirect()->back()->with('success', 'Asistencia eliminada.');
    }

    public function importarReloj(Request $request)
    {
        $request->validate([
            'archivo_reloj' => 'required|file|max:10240',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ], [
            'archivo_reloj.uploaded' => 'El archivo no pudo subirse. Revisa el limite de carga de PHP o intenta con un archivo mas pequeno.',
            'archivo_reloj.max' => 'El archivo del reloj no debe pesar mas de 10 MB.',
        ]);

        $archivoReloj = $request->file('archivo_reloj');
        $extension = strtolower($archivoReloj->getClientOriginalExtension());

        if (!in_array($extension, ['csv', 'txt'], true)) {
            return redirect()->back()->withErrors([
                'archivo_reloj' => 'Selecciona un archivo .csv o .txt del reloj checador.',
            ]);
        }

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
            $archivoReloj->getRealPath(),
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
        $asistenciasSincronizar = [];

        foreach ($validated['filas'] as $fila) {
            if (!($fila['aprobado'] ?? false) || empty($fila['empleado_id'])) {
                $omitidas++;
                continue;
            }

            $tipoAsistencia = $fila['tipo_asistencia'];
            $horaEntrada = $tipoAsistencia === 'Normal' ? ($fila['hora_entrada'] ?? null) : null;
            $horaSalida = $tipoAsistencia === 'Normal' ? ($fila['hora_salida'] ?? null) : null;

            if ($tipoAsistencia === 'Normal' && (!$horaEntrada || !$horaSalida)) {
                $omitidas++;
                continue;
            }

            $empleado = Empleado::find($fila['empleado_id']);
            $datosCalculados = $this->calcularHoras($fila['fecha'], $horaEntrada, $horaSalida, $tipoAsistencia, $empleado);

            $asistencia = Asistencia::updateOrCreate(
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

            $asistenciasSincronizar[] = $asistencia->fresh('empleado');
            $guardadas++;
        }

        FirebaseSyncService::sincronizarAsistencias($asistenciasSincronizar);

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

    private function inicioSemanaNomina(string $fecha): Carbon
    {
        $inicio = Carbon::parse($fecha)->startOfDay();

        while (!$inicio->isThursday()) {
            $inicio->subDay();
        }

        return $inicio;
    }

    private function prepararRevisionImportacion(string $path, ?string $fechaInicio, ?string $fechaFin): array
    {
        [$agrupados, $fechasCsv] = $this->leerMarcajesCsv($path);

        $rango = $this->resolverRangoRevision($fechasCsv, $fechaInicio, $fechaFin);
        $empleados = Empleado::where('estatus', true)
            ->orderByRaw("CAST(COALESCE(NULLIF(numero_empleado, ''), NULLIF(numero_empleado_baja, ''), id) AS UNSIGNED) ASC")
            ->orderBy('nombre_completo', 'asc')
            ->get();
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

                $horario = $this->resolverHorarioMarcajes($fecha, $horas);
                $horaEntrada = $horario['hora_entrada'];
                $horaSalida = $horario['hora_salida'];

                $datosCalculados = $this->calcularHoras($fecha, $horaEntrada, $horaSalida, 'Normal', $empleado);

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
                        'marcas' => $horario['marcas'],
                    ], $datosCalculados);
                    continue;
                }

                $clave = $this->claveAsistencia($empleado->id, $fecha);
                $clavesConCsv[$clave] = true;
                $yaExiste = isset($existentes[$clave]);
                $detectadasCsv++;

                $estado = $horario['incompleta'] ? 'incompleta' : ($yaExiste ? 'actualiza' : 'detectada');
                $mensaje = $horario['incompleta']
                    ? $horario['mensaje']
                    : ($yaExiste ? 'Ya habia una asistencia para este dia; al aprobar se actualizara.' : $horario['mensaje']);

                $filas[] = array_merge([
                    'aprobado' => !$horario['incompleta'],
                    'empleado_id' => $empleado->id,
                    'numero_empleado' => $empleado->numero_empleado,
                    'nombre_completo' => $empleado->nombre_completo,
                    'fecha' => $fecha,
                    'tipo_asistencia' => 'Normal',
                    'hora_entrada' => $horaEntrada,
                    'hora_salida' => $horaSalida,
                    'estado' => $estado,
                    'mensaje' => $mensaje,
                    'marcas' => $horario['marcas'],
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
            return [
                $this->numeroOrdenRevision($a),
                $a['nombre_completo'] ?? '',
                $a['fecha'] ?? '',
                $a['estado'] ?? '',
            ] <=> [
                $this->numeroOrdenRevision($b),
                $b['nombre_completo'] ?? '',
                $b['fecha'] ?? '',
                $b['estado'] ?? '',
            ];
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

    private function resolverHorarioMarcajes(string $fecha, array $horas): array
    {
        $marcas = array_values(array_unique(array_filter($horas)));
        sort($marcas);

        $entrada = null;
        $salida = null;
        $mensaje = 'Marcajes detectados en CSV.';

        if (count($marcas) === 1) {
            $marca = $marcas[0];

            if ($this->horaMenorOIgual($marca, '12:00')) {
                $entrada = $marca;
            } else {
                $salida = $marca;
            }
        } elseif (count($marcas) > 1) {
            $entrada = $this->primeraMarcaAntesDe($marcas, '12:00');
            $salida = $this->ultimaMarcaDespuesDe($marcas, '10:00');

            if ($entrada && $salida && !$this->horaMayorQue($salida, $entrada)) {
                $salida = null;
            }
        }

        if (Carbon::parse($fecha)->isWednesday() && $entrada && !$salida) {
            $salida = '17:30';
            $mensaje = 'Miercoles sin salida: se ajusto salida a 17:30 para el corte.';
        }

        $incompleta = !$entrada || !$salida;

        if ($incompleta) {
            $mensaje = $this->mensajeMarcajeIncompleto($entrada, $salida);
        }

        return [
            'hora_entrada' => $entrada,
            'hora_salida' => $salida,
            'incompleta' => $incompleta,
            'mensaje' => $mensaje,
            'marcas' => count($marcas),
        ];
    }

    private function primeraMarcaAntesDe(array $marcas, string $limite): ?string
    {
        foreach ($marcas as $marca) {
            if ($this->horaMenorOIgual($marca, $limite)) {
                return $marca;
            }
        }

        return null;
    }

    private function ultimaMarcaDespuesDe(array $marcas, string $limite): ?string
    {
        $resultado = null;

        foreach ($marcas as $marca) {
            if ($this->horaMayorOIgual($marca, $limite)) {
                $resultado = $marca;
            }
        }

        return $resultado;
    }

    private function mensajeMarcajeIncompleto(?string $entrada, ?string $salida): string
    {
        if ($entrada && !$salida) {
            return "Marca incompleta: solo se detecto entrada {$entrada}. Captura la salida antes de aprobar.";
        }

        if (!$entrada && $salida) {
            return "Marca incompleta: solo se detecto posible salida {$salida}. Captura la entrada antes de aprobar.";
        }

        return 'Marca incompleta: revisa entrada y salida antes de aprobar.';
    }

    private function horaMenorOIgual(string $hora, string $limite): bool
    {
        return $this->minutosHora($hora) <= $this->minutosHora($limite);
    }

    private function horaMayorOIgual(string $hora, string $limite): bool
    {
        return $this->minutosHora($hora) >= $this->minutosHora($limite);
    }

    private function horaMayorQue(string $hora, string $limite): bool
    {
        return $this->minutosHora($hora) > $this->minutosHora($limite);
    }

    private function minutosHora(string $hora): int
    {
        [$horas, $minutos] = array_pad(explode(':', substr($hora, 0, 5)), 2, 0);

        return ((int) $horas * 60) + (int) $minutos;
    }

    private function leerMarcajesCsv(string $path): array
    {
        $file = fopen($path, 'r');

        if (!$file) {
            return [[], []];
        }

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

    private function numeroOrdenRevision(array $fila): int
    {
        $numero = $this->normalizarNumeroEmpleado((string) ($fila['numero_empleado'] ?? $fila['csv_numero_empleado'] ?? ''));

        return is_numeric($numero) ? (int) $numero : PHP_INT_MAX;
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
            if (!$cursor->isWeekend()) {
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
        $hora = preg_replace('/\bA\s*M\b/u', 'AM', $hora);
        $hora = preg_replace('/\bP\s*M\b/u', 'PM', $hora);
        $hora = preg_replace('/\s+/', ' ', $hora);

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

    private function calcularHoras($fecha, $hora_entrada, $hora_salida, $tipo_asistencia, ?Empleado $empleado = null)
    {
        $minutos_tarde = 0;
        $horas_normales = 0;
        $horas_extra_diarias = 0;

        if ($tipo_asistencia === 'Normal' && $hora_entrada && $hora_salida) {
            $fecha_carbon = Carbon::parse($fecha);
            $entrada = Carbon::parse($fecha . ' ' . $hora_entrada);
            $salida = Carbon::parse($fecha . ' ' . $hora_salida);
            $hora_oficial = Carbon::parse($fecha . ' 08:00:00');

            if ($salida->lessThanOrEqualTo($entrada)) {
                return [
                    'minutos_tarde' => 0,
                    'horas_trabajadas' => 0,
                    'horas_extra' => 0,
                ];
            }

            if ($entrada->greaterThan($hora_oficial)) {
                $minutos_tarde = $hora_oficial->diffInMinutes($entrada);
            }

            if ($fecha_carbon->isSaturday()) {
                $inicioSabado = $entrada->lessThan($hora_oficial) ? $hora_oficial : $entrada;
                $horas_extra_diarias = $this->redondearHoraCompletaCercana(max(0, $inicioSabado->diffInMinutes($salida) / 60));
            } else {
                $limite_normal = Carbon::parse($fecha . ' 17:30:00');
                $inicioJornada = $entrada->lessThan($hora_oficial) || $minutos_tarde < 30
                    ? $hora_oficial
                    : $entrada;

                if ($salida->greaterThan($limite_normal)) {
                    $horas_normales = max(0, $inicioJornada->diffInMinutes($limite_normal) / 60);
                    $horas_extra_diarias = $this->redondearHoraCompletaInferior(max(0, $limite_normal->diffInMinutes($salida) / 60));
                } else {
                    $horas_normales = max(0, $inicioJornada->diffInMinutes($salida) / 60);
                }
            }
        }

        if ($empleado) {
            if ((bool) ($empleado->es_estudiante ?? false)) {
                $horas_normales += $horas_extra_diarias;
                $horas_extra_diarias = 0;
                $minutos_tarde = 0;
            } else {
                if (ReglasNominaEmpleado::sinRetardos($empleado)) {
                    $minutos_tarde = 0;
                }

                if (ReglasNominaEmpleado::sinHorasExtra($empleado)) {
                    $horas_extra_diarias = 0;
                }
            }
        }

        return [
            'minutos_tarde' => $minutos_tarde,
            'horas_trabajadas' => round($horas_normales, 2),
            'horas_extra' => $horas_extra_diarias,
        ];
    }

    private function redondearHoraCompletaInferior(float $horas): float
    {
        return floor($horas);
    }

    private function redondearHoraCompletaCercana(float $horas): float
    {
        return max(0, round($horas));
    }

    private function queryAlumnosActivos()
    {
        return Empleado::where('estatus', true)
            ->where('es_estudiante', true)
            ->orderByRaw("CAST(COALESCE(NULLIF(numero_empleado, ''), NULLIF(numero_empleado_baja, ''), id) AS UNSIGNED) ASC")
            ->orderBy('nombre_completo', 'asc');
    }

    private function resolverIdsSeleccionados($ids): array
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        return collect($ids)
            ->flatten()
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function registrosHorasServicio($asistencias)
    {
        return collect($asistencias)
            ->sortBy('fecha')
            ->map(function (Asistencia $asistencia) {
                $horas = $this->horasServicioAsistencia($asistencia);

                return [
                    'fecha' => Carbon::parse($asistencia->fecha)->locale('es')->isoFormat('D-MMM-YY'),
                    'hora_entrada' => $this->horaCorta($asistencia->hora_entrada),
                    'hora_salida' => $this->horaCorta($asistencia->hora_salida),
                    'horas' => $horas,
                    'horas_texto' => $this->formatoHorasServicio($horas),
                ];
            })
            ->values();
    }

    private function horasServicioAsistencia(Asistencia $asistencia): float
    {
        $fecha = Carbon::parse($asistencia->fecha);

        if ($fecha->isSaturday() && $asistencia->hora_entrada && $asistencia->hora_salida) {
            $entrada = Carbon::parse($fecha->format('Y-m-d') . ' ' . $asistencia->hora_entrada);
            $salida = Carbon::parse($fecha->format('Y-m-d') . ' ' . $asistencia->hora_salida);

            if ($salida->greaterThan($entrada)) {
                $horaOficial = Carbon::parse($fecha->format('Y-m-d') . ' 08:00:00');
                $inicio = $entrada->lessThan($horaOficial) ? $horaOficial : $entrada;

                return max(0, round($inicio->diffInMinutes($salida) / 60));
            }
        }

        $horasGuardadas = (float) $asistencia->horas_trabajadas + (float) $asistencia->horas_extra;

        if ($horasGuardadas > 0) {
            return round($horasGuardadas, 2);
        }

        if (!$asistencia->hora_entrada || !$asistencia->hora_salida) {
            return 0;
        }

        $entrada = Carbon::parse($fecha->format('Y-m-d') . ' ' . $asistencia->hora_entrada);
        $salida = Carbon::parse($fecha->format('Y-m-d') . ' ' . $asistencia->hora_salida);

        if ($salida->lessThanOrEqualTo($entrada)) {
            return 0;
        }

        return round($entrada->diffInMinutes($salida) / 60, 2);
    }

    private function horaCorta($hora): string
    {
        if (!$hora) {
            return '';
        }

        return Carbon::parse('2000-01-01 ' . $hora)->format('H:i');
    }

    private function formatoHorasServicio(float $horas): string
    {
        $horas = round($horas, 2);
        $enteras = (int) floor($horas);
        $minutos = (int) round(($horas - $enteras) * 60);

        if ($minutos === 60) {
            $enteras++;
            $minutos = 0;
        }

        if ($minutos === 0) {
            return $enteras . ' HRS';
        }

        if ($minutos === 30) {
            return $enteras . ' 1/2 HRS';
        }

        return $enteras . ':' . str_pad((string) $minutos, 2, '0', STR_PAD_LEFT) . ' HRS';
    }

    private function rangoSemanaTexto(Carbon $inicioSemana, Carbon $finSemana): string
    {
        return $inicioSemana->locale('es')->isoFormat('D MMM YYYY')
            . ' al '
            . $finSemana->locale('es')->isoFormat('D MMM YYYY');
    }
}
