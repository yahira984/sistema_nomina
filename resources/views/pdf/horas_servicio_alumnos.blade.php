<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Horas Alumnos</title>
    <style>
        @page {
            size: letter portrait;
            margin: 0.25in;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        .page {
            height: 10.25in;
            page-break-inside: avoid;
        }

        .slot {
            height: 4.98in;
            box-sizing: border-box;
            overflow: hidden;
            padding: 0.04in 0.03in;
            page-break-inside: avoid;
        }

        .slot:first-child {
            margin-bottom: 0.08in;
            padding-bottom: 0.05in;
            border-bottom: 1px dashed #94a3b8;
        }

        .sheet {
            height: 100%;
            box-sizing: border-box;
            border: 1px solid #111827;
            padding: 6px 8px;
            page-break-inside: avoid;
        }

        .company {
            color: #1e5f88;
            font-size: 9px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
        }

        .title {
            margin: 3px 0 5px;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-align: center;
            text-transform: uppercase;
        }

        .meta {
            width: 100%;
            margin-bottom: 5px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .meta td {
            padding: 1px 3px;
            vertical-align: bottom;
        }

        .label {
            width: 1.25in;
            font-weight: 800;
            white-space: nowrap;
        }

        .line {
            border-bottom: 1px solid #111827;
            font-weight: 700;
            min-height: 13px;
        }

        .hours-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .hours-table th,
        .hours-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 3px;
            text-align: center;
            vertical-align: middle;
        }

        .hours-table th {
            background: #2f87b7;
            color: #ffffff;
            font-size: 9px;
            font-weight: 800;
        }

        .hours-table tbody tr:nth-child(even) td {
            background: #eef7fb;
        }

        .hours-table .total-row td {
            background: #fef08a !important;
            color: #1f2937;
            font-weight: 900;
            text-transform: uppercase;
        }

        .firm-cell {
            height: 17px;
        }

        .footer-total {
            margin-top: 3px;
            font-size: 9px;
            font-weight: 800;
        }

        .empty {
            padding: 1in 0;
            border: 1px dashed #cbd5e1;
            color: #64748b;
            font-weight: 800;
            text-align: center;
        }
    </style>
</head>
<body>
    @if(collect($alumnos)->isEmpty())
        <div class="empty">No hay alumnos seleccionados para este formato.</div>
    @else
        @foreach(collect($alumnos)->chunk(2) as $paginaAlumnos)
            <div class="page" style="page-break-after: {{ $loop->last ? 'auto' : 'always' }};">
                @foreach($paginaAlumnos as $alumno)
                    @php
                        $empleado = $alumno['empleado'];
                        $registros = collect($alumno['registros'])->take(7);
                        $filasVacias = max(0, 7 - $registros->count());
                        $fechaIngreso = $empleado->fecha_ingreso
                            ? \Carbon\Carbon::parse($empleado->fecha_ingreso)->format('d/m/Y')
                            : '';
                        $numeroEmpleado = $empleado->numero_empleado ?? $empleado->numero_empleado_baja ?? 'S/N';
                        $horasCumplirTexto = $horasCumplir !== '' && $horasCumplir !== null
                            ? rtrim(rtrim(number_format((float) $horasCumplir, 2, '.', ''), '0'), '.')
                            : '';
                    @endphp

                    <div class="slot">
                        <div class="sheet">
                            <div class="company">PROMATEC SERVICIOS TECNICOS S.A. de C.V. / LUGARTH</div>
                            <div class="title">Registro de Horas</div>

                            <table class="meta">
                                <tr>
                                    <td class="label">Nombre del prestante:</td>
                                    <td class="line" colspan="3">#{{ $numeroEmpleado }} - {{ strtoupper($empleado->nombre_completo ?? '') }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Universidad:</td>
                                    <td class="line" colspan="3">{{ strtoupper($universidad ?? '') }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Fecha de ingreso:</td>
                                    <td class="line">{{ $fechaIngreso }}</td>
                                    <td class="label" style="width: 1.15in;">Por cumplir:</td>
                                    <td class="line">{{ $horasCumplirTexto ? $horasCumplirTexto . ' horas' : '' }}</td>
                                </tr>
                            </table>

                            <table class="hours-table">
                                <thead>
                                    <tr>
                                        <th style="width: 16%;">Fecha</th>
                                        <th style="width: 17%;">Hora Entrada</th>
                                        <th style="width: 17%;">Hora Salida</th>
                                        <th style="width: 18%;">Total de Horas</th>
                                        <th style="width: 16%;">Firma Alumno</th>
                                        <th style="width: 16%;">Firma Supervisor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($registros as $registro)
                                        <tr>
                                            <td>{{ $registro['fecha'] }}</td>
                                            <td>{{ $registro['hora_entrada'] }}</td>
                                            <td>{{ $registro['hora_salida'] }}</td>
                                            <td>{{ $registro['horas_texto'] }}</td>
                                            <td class="firm-cell"></td>
                                            <td class="firm-cell"></td>
                                        </tr>
                                    @endforeach

                                    @for($i = 0; $i < $filasVacias; $i++)
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td class="firm-cell"></td>
                                            <td class="firm-cell"></td>
                                        </tr>
                                    @endfor

                                    <tr class="total-row">
                                        <td colspan="3">Semana {{ $numeroSemana }}</td>
                                        <td colspan="3">Total {{ $alumno['total_horas_texto'] ?? '0 HRS' }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="footer-total">
                                Total de horas registradas: {{ $alumno['total_horas'] > 0 ? rtrim(rtrim(number_format((float) $alumno['total_horas'], 2, '.', ''), '0'), '.') : '0' }} hrs
                                <span style="float: right;">{{ strtoupper($rangoSemana) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif
</body>
</html>
