<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Sueldo</title>
</head>
<body>
    @php
        $empleadoRecibo = $empleado ?? $nomina->empleado;
        $esEstudiante = $es_estudiante ?? (bool) ($empleadoRecibo->es_estudiante ?? false);
        $sueldoSemanalRecibo = $sueldo_semanal ?? ($empleadoRecibo->sueldo_semanal ?? 0);
        $sueldoHoraRecibo = $sueldo_por_hora ?? ($empleadoRecibo->sueldo_por_hora ?? 0);
        $tarifaBaseHoraRecibo = $tarifa_base_hora ?? ($esEstudiante ? $sueldoHoraRecibo : ($sueldoSemanalRecibo > 0 ? $sueldoSemanalRecibo / 48 : 0));
        
        $pagoNormal = $pago_normal ?? 0;
        $pagoExtra = $pago_extra ?? 0;
        $pagoIncapacidad = $pago_incapacidad ?? 0;
        $pagoVacaciones = $pago_vacaciones ?? 0;
        
        $descuentoFaltas = $descuento_faltas ?? 0;
        $descuentoRetardos = $descuento_retardos ?? 0;
        $deduccionPrestamo = $deduccion_prestamo ?? ($empleadoRecibo->cuota_prestamo ?? 0);
        $descuentoImss = $descuento_imss ?? ($empleadoRecibo->descuento_imss ?? 0);
        $descuentoIsr = $descuento_isr ?? ($empleadoRecibo->descuento_isr ?? 0);
        $descuentoInfonavit = $descuento_infonavit ?? ($empleadoRecibo->descuento_infonavit ?? 0);
        
        $totalPercepciones = $total_percepciones ?? $nomina->total_percepciones ?? 0;
        $totalDeducciones = $total_deducciones ?? $nomina->total_deducciones ?? 0;
        $pagoNeto = $pago_neto ?? $nomina->pago_neto ?? 0;

        $cell = 'border: 1px solid #cbd5e1; padding: 7px; font-family: Arial; font-size: 12px;';
        $moneyFormat = '"$"#,##0.00';
    @endphp

    <table style="border-collapse: collapse;">
        <tr>
            <td colspan="2" style="border: none;">
                <img src="{{ public_path('img/promatec.png') }}" alt="Promatec" height="50">
                <img src="{{ public_path('img/lugarth.png') }}" alt="Lugarth" height="50">
            </td>
            <td colspan="2" style="border: none; text-align: right; font-size: 10px;">
                BARRIO DE SANTO TOMAS C.P. 43860<br>PACHUCA DE SOTO, HGO
            </td>
        </tr>

        <tr>
            <td colspan="4" style="{{ $cell }} text-align: center; background-color: #0f172a; color: #ffffff; font-weight: bold;">
                RECIBO DE SUELDO PACHUCA
            </td>
        </tr>

        <tr>
            <td colspan="2" style="{{ $cell }}">
                Nombre: {{ strtoupper($empleadoRecibo->nombre_completo ?? 'N/A') }}
            </td>
            <td colspan="2" style="{{ $cell }} text-align: center;">
                SEMANA {{ $nomina->numero_semana ?? 'N/A' }}
            </td>
        </tr>

        <tr>
            <th colspan="2" style="{{ $cell }} background-color: #f1f5f9;">PERCEPCIONES</th>
            <th colspan="2" style="{{ $cell }} background-color: #f1f5f9;">DEDUCCIONES</th>
        </tr>

        <tr>
            <td style="{{ $cell }}">SUELDO</td>
            <td style="{{ $cell }} text-align: right;">TOTAL</td>
            <td style="{{ $cell }}">CONCEPTO</td>
            <td style="{{ $cell }} text-align: right;">TOTAL</td>
        </tr>

        <tr>
            <td style="{{ $cell }} vertical-align: top;">
                {{ $esEstudiante ? 'Sueldo Hora' : 'Sueldo Semanal' }}<br>
                Horas Normales<br>
                Horas Extra<br>
                Incapacidad<br>
                Vacaciones
            </td>
            <td style="{{ $cell }} text-align: right;" data-format="{{ $moneyFormat }}">
                {{ $pagoNormal }}<br>
                {{ $pagoNormal }}<br>
                {{ $pagoExtra }}<br>
                {{ $pagoIncapacidad }}<br>
                {{ $pagoVacaciones }}
            </td>
            <td style="{{ $cell }} vertical-align: top;">
                Faltas<br>
                Retardos<br>
                Préstamos<br>
                IMSS<br>
                ISR<br>
                INFONAVIT
            </td>
            <td style="{{ $cell }} text-align: right;" data-format="{{ $moneyFormat }}">
                {{ $descuentoFaltas }}<br>
                {{ $descuentoRetardos }}<br>
                {{ $deduccionPrestamo }}<br>
                {{ $descuentoImss }}<br>
                {{ $descuentoIsr }}<br>
                {{ $descuentoInfonavit }}
            </td>
        </tr>

        <tr>
            <td colspan="2" style="{{ $cell }} font-weight: bold;" data-format="{{ $moneyFormat }}">
                TOTAL PERCEPCIONES: {{ $totalPercepciones }}
            </td>
            <td colspan="2" style="{{ $cell }} font-weight: bold;" data-format="{{ $moneyFormat }}">
                TOTAL DEDUCCIONES: {{ $totalDeducciones }}
            </td>
        </tr>

        <tr>
            <td colspan="4" style="{{ $cell }} text-align: right; background-color: #ecfdf5; font-weight: bold; font-size: 14px;" data-format="{{ $moneyFormat }}">
                NETO A PAGAR: {{ $pagoNeto }}
            </td>
        </tr>
    </table>
</body>
</html>