<table>
    <thead>
        <tr>
            <th colspan="4" style="font-weight: bold; font-size: 14px; text-align: center; background-color: #f3f4f6;">
                SISTEMA DE NÓMINAS PROMATEC-LUGARTH
            </th>
        </tr>
        <tr>
            <th colspan="4" style="font-weight: bold; font-size: 12px; text-align: center;">
                RECIBO DE NÓMINA INDIVIDUAL - SEMANA {{ $nomina->numero_semana ?? 'N/A' }}
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="font-weight: bold;">No. Empleado:</td>
            <td>{{ $empleado->numero_empleado ?? 'N/A' }}</td>
            <td style="font-weight: bold;">Fecha Inicio:</td>
            <td>{{ \Carbon\Carbon::parse($nomina->fecha_inicio)->locale('es')->isoFormat('D [de] MMMM YYYY') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Empleado:</td>
            <td>{{ $empleado->nombre_completo ?? 'N/A' }}</td>
            <td style="font-weight: bold;">Fecha Fin:</td>
            <td>{{ \Carbon\Carbon::parse($nomina->fecha_fin)->locale('es')->isoFormat('D [de] MMMM YYYY') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Puesto:</td>
            <td>{{ $empleado->puesto ?? 'N/A' }}</td>
            <td style="font-weight: bold;">Tarifa por hora:</td>
            <td>${{ number_format($empleado->sueldo_por_hora ?? 0, 2) }}</td>
        </tr>
        <tr><td></td><td></td><td></td><td></td></tr>

        <tr>
            <th colspan="2" style="background-color: #d1fae5; font-weight: bold;">Percepciones</th>
            <th colspan="2" style="background-color: #fee2e2; font-weight: bold;">Deducciones</th>
        </tr>
        <tr>
            <td>Sueldo Base ({{ $nomina->horas_normales ?? 0 }} hrs normales):</td>
            <td>${{ number_format($pago_normal ?? 0, 2) }}</td>
            <td>Descuento Faltas:</td>
            <td>${{ number_format($descuento_faltas ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td>Pago Horas Extra ({{ $nomina->horas_extra ?? 0 }} hrs):</td>
            <td>${{ number_format($pago_extra ?? 0, 2) }}</td>
            <td>Descuento Retardos:</td>
            <td>${{ number_format($descuento_retardos ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td>Pago Vacaciones:</td>
            <td>${{ number_format($pago_vacaciones ?? 0, 2) }}</td>
            <td>Otras Deducciones:</td>
            <td>${{ number_format(($total_deducciones ?? 0) - ($descuento_faltas ?? 0) - ($descuento_retardos ?? 0), 2) }}</td>
        </tr>
        <tr><td></td><td></td><td></td><td></td></tr>

        <tr>
            <td style="font-weight: bold;">Total Percepciones:</td>
            <td style="font-weight: bold; color: #047857;">${{ number_format($total_percepciones ?? 0, 2) }}</td>
            <td style="font-weight: bold;">Total Deducciones:</td>
            <td style="font-weight: bold; color: #b91c1c;">${{ number_format($total_deducciones ?? 0, 2) }}</td>
        </tr>
        <tr style="background-color: #e2e8f0;">
            <td colspan="2" style="font-weight: bold; text-align: right;">NETO A PAGAR:</td>
            <td colspan="2" style="font-weight: bold; color: #047857;">${{ number_format($pago_neto ?? 0, 2) }}</td>
        </tr>
    </tbody>
</table>