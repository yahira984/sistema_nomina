<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AsistenciaController extends Controller
{
    public function index()
    {
        $empleados = Empleado::where('estatus', true)->orderBy('nombre_completo', 'asc')->get();
        $asistencias = Asistencia::with('empleado')->orderBy('fecha', 'desc')->get();

        return Inertia::render('Asistencias/Index', [
            'empleados' => $empleados,
            'asistencias' => $asistencias
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

        return redirect()->back()->with('success', 'Asistencia registrada con éxito.');
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
        $Asistencia = Asistencia::findOrFail($id);
        $Asistencia->delete();
        return redirect()->back()->with('success', 'Asistencia eliminada.');
    }

    // --- LA LICUADORA DEL RELOJ BIOMÉTRICO ---
    public function importarReloj(Request $request)
    {
        $request->validate([
            'archivo_reloj' => 'required|file|mimes:csv,txt'
        ]);

        $path = $request->file('archivo_reloj')->getRealPath();
        
        $file = fopen($path, "r");
        
        // Saltamos las 2 primeras líneas (Título general y Encabezados de tu reloj)
        fgetcsv($file);
        fgetcsv($file);

        $agrupados = [];

        // PASO 1: Agrupar la basura del reloj
        while (($fila = fgetcsv($file)) !== FALSE) {
            if (count($fila) < 4) continue; // Si la fila está mocha, la ignoramos

            $numero_empleado = trim($fila[0]);
            $fecha_reloj = trim($fila[2]); 
            $hora_reloj = trim($fila[3]);

            if (!$numero_empleado || !$fecha_reloj || !$hora_reloj) continue;

            try {
                // Formateamos la fecha (Mes/Día/Año -> Año-Mes-Día)
                $fecha = Carbon::createFromFormat('m/d/Y', $fecha_reloj)->format('Y-m-d');
                $agrupados[$numero_empleado][$fecha][] = $hora_reloj;
            } catch (\Exception $e) {
                // Si la fecha viene rara, saltamos el registro
                continue;
            }
        }
        fclose($file);

        // PASO 2: Sacar Mínimos, Máximos y Guardar
        foreach ($agrupados as $num_empleado => $fechas) {
            $empleado = Empleado::where('numero_empleado', $num_empleado)->first();
            
            // Si el ID del reloj no cuadra con nadie en el sistema, lo ignoramos
            if (!$empleado) continue;

            foreach ($fechas as $fecha => $horas) {
                // Acomodamos las horas de menor a mayor (Cronológicamente)
                sort($horas);

                $hora_entrada = $horas[0]; // La primera que checó
                
                // Si checó más de 1 vez, la última es su salida. Si solo checó 1 vez, salida es null.
                $hora_salida = count($horas) > 1 ? end($horas) : null;

                // Aprovechamos tu cerebro de cálculo para sacar retardos y extras de este registro
                $datosCalculados = $this->calcularHoras($fecha, $hora_entrada, $hora_salida, 'Normal');

                Asistencia::updateOrCreate(
                    [
                        'empleado_id' => $empleado->id,
                        'fecha' => $fecha,
                    ],
                    array_merge([
                        'tipo_asistencia' => 'Normal',
                        'hora_entrada' => $hora_entrada,
                        'hora_salida' => $hora_salida,
                    ], $datosCalculados)
                );
            }
        }

        return redirect()->back()->with('success', '¡Archivo del reloj procesado y limpiado correctamente!');
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
                // Sábado se va todo a extra
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
            'horas_extra' => $horas_extra_diarias
        ];
    }
}