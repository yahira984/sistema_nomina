<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial total de horas</title>
    <style>
        @page { size: letter portrait; margin: .42in; }
        body { color:#172033; font-family:Arial,sans-serif; font-size:10px; }
        .student { page-break-after:always; }
        .student:last-child { page-break-after:auto; }
        h1 { margin:0 0 3px; color:#075985; font-size:17px; text-align:center; }
        .subtitle { margin-bottom:14px; color:#475569; font-weight:bold; text-align:center; }
        .meta,.hours { width:100%; border-collapse:collapse; }
        .meta td { border:1px solid #cbd5e1; padding:6px; }
        .meta .label { width:20%; background:#f1f5f9; font-weight:bold; }
        .summary { margin:12px 0; width:100%; border-collapse:separate; border-spacing:6px 0; }
        .summary td { border:1px solid #bae6fd; background:#f0f9ff; padding:8px; text-align:center; }
        .summary strong { display:block; margin-top:3px; color:#075985; font-size:15px; }
        .hours th,.hours td { border:1px solid #cbd5e1; padding:5px; text-align:center; }
        .hours th { background:#0369a1; color:white; }
        .hours tbody tr:nth-child(even) { background:#f8fafc; }
        .signatures { margin-top:35px; width:100%; }
        .signatures td { width:50%; padding:0 28px; text-align:center; }
        .line { border-top:1px solid #172033; padding-top:4px; }
    </style>
</head>
<body>
@forelse($alumnos as $alumno)
    @php
        $empleado = $alumno['empleado'];
        $resumen = $alumno['resumen'] ?? [];
        $numero = $empleado->numero_empleado ?? $empleado->numero_empleado_baja ?? 'S/N';
        $f = fn($v) => rtrim(rtrim(number_format((float)($v ?? 0), 2, '.', ''), '0'), '.');
    @endphp
    <section class="student">
        <h1>HISTORIAL TOTAL DE SERVICIO SOCIAL</h1>
        <div class="subtitle">PROMATEC SERVICIOS TECNICOS S.A. DE C.V. / LUGARTH</div>
        <table class="meta">
            <tr><td class="label">Alumno</td><td>{{ strtoupper($empleado->nombre_completo) }}</td><td class="label">Núm. empleado</td><td>{{ $numero }}</td></tr>
            <tr><td class="label">Universidad</td><td colspan="3">{{ strtoupper($empleado->universidad ?: 'NO REGISTRADA') }}</td></tr>
            <tr><td class="label">Carrera</td><td colspan="3">{{ strtoupper($empleado->carrera ?: 'NO REGISTRADA') }}</td></tr>
            <tr><td class="label">Inicio</td><td>{{ optional($empleado->fecha_inicio_servicio)->format('d/m/Y') ?: 'No registrado' }}</td><td class="label">Fecha límite</td><td>{{ optional($empleado->fecha_limite_servicio)->format('d/m/Y') ?: 'No registrada' }}</td></tr>
        </table>
        <table class="summary"><tr>
            <td>Requeridas<strong>{{ $f($resumen['horas_requeridas'] ?? 0) }} h</strong></td>
            <td>Cumplidas<strong>{{ $f($resumen['horas_cumplidas'] ?? 0) }} h</strong></td>
            <td>Restantes<strong>{{ $f($resumen['horas_restantes'] ?? 0) }} h</strong></td>
            <td>Progreso<strong>{{ $f($resumen['porcentaje'] ?? 0) }}%</strong></td>
        </tr></table>
        <table class="hours">
            <thead><tr><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Horas del día</th></tr></thead>
            <tbody>
            @forelse($alumno['registros'] as $registro)
                <tr><td>{{ $registro['fecha'] }}</td><td>{{ $registro['hora_entrada'] }}</td><td>{{ $registro['hora_salida'] }}</td><td>{{ $registro['horas_texto'] }}</td></tr>
            @empty
                <tr><td colspan="4">Sin horas registradas desde el inicio del servicio.</td></tr>
            @endforelse
            </tbody>
        </table>
        <table class="signatures"><tr><td><div class="line">Firma del alumno</div></td><td><div class="line">Firma del supervisor</div></td></tr></table>
    </section>
@empty
    <p>No hay alumnos seleccionados.</p>
@endforelse
</body>
</html>
