<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Sueldo</title>
</head>
<body>
    @php
        $empleadoRecibo = $empleado ?? $nomina->empleado;
        $numeroEmpleado = $empleadoRecibo->numero_empleado ?? $empleadoRecibo->numero_empleado_baja ?? 'S/N';
        $esEstudiante = $es_estudiante ?? (bool) ($empleadoRecibo->es_estudiante ?? false);
        $sueldoSemanalRecibo = $sueldo_semanal ?? ($empleadoRecibo->sueldo_semanal ?? 0);
        $sueldoHoraRecibo = $sueldo_por_hora ?? ($empleadoRecibo->sueldo_por_hora ?? 0);
        $sueldoDiario = $pago_dia_planta ?? ($sueldoSemanalRecibo > 0 ? $sueldoSemanalRecibo / 7 : 0);
        $tarifaBaseHoraRecibo = $tarifa_base_hora ?? ($esEstudiante ? $sueldoHoraRecibo : ($sueldoSemanalRecibo > 0 ? $sueldoSemanalRecibo / 56 : 0));

        $horasNormales = $nomina->horas_normales ?? 0;
        $horasExtra = $nomina->horas_extra ?? 0;
        $horasExtraPeriodo = $horas_extra_periodo ?? $horasExtra;
        $horasExtraMiercolesAnterior = $horas_extra_miercoles_anterior ?? 0;
        $horasExtraPagadas = $horas_extra_pagadas ?? ($nomina->horas_extra_pagadas ?? $horasExtra);
        $horasAdeudoDescontadas = $horas_adeudo_descontadas ?? ($nomina->horas_adeudo_descontadas ?? 0);
        $pagoNormal = $pago_normal ?? 0;
        $pagoExtra = $pago_extra ?? 0;
        $pagoIncapacidad = $pago_incapacidad ?? 0;
        $pagoVacaciones = $pago_vacaciones ?? 0;
        $prestamoOtorgado = $prestamo_otorgado ?? ($nomina->prestamo_otorgado ?? 0);

        $diasFalta = $dias_falta ?? 0;
        $diasFaltaPagados = $dias_falta_pagados ?? ($nomina->faltas_pagadas ?? 0);
        $diasFaltaDescontables = $dias_falta_descontables ?? max(0, $diasFalta - $diasFaltaPagados);
        $diasIncapacidad = $dias_incapacidad ?? 0;
        $diasVacaciones = $dias_vacaciones_pagadas ?? $dias_vacaciones ?? ($nomina->dias_vacaciones_pagadas ?? 0);
        $minutosTarde = $minutos_tarde_acumulados ?? 0;
        $minutosTardeDescontables = $minutos_tarde_descontables ?? $minutosTarde;

        $descuentoFaltas = $descuento_faltas ?? 0;
        $descuentoRetardos = $descuento_retardos ?? 0;
        $prestamoDescuento = $prestamo_descuento ?? $deduccion_prestamo ?? ($nomina->prestamo_descuento ?? 0);
        $deduccionManual = $deduccion_manual ?? ($nomina->deduccion_manual ?? 0);
        $descuentoImss = $descuento_imss ?? ($empleadoRecibo->descuento_imss ?? 0);
        $descuentoIsr = $descuento_isr ?? ($empleadoRecibo->descuento_isr ?? 0);
        $descuentoInfonavit = $descuento_infonavit ?? ($empleadoRecibo->descuento_infonavit ?? 0);

        $totalPercepciones = $total_percepciones ?? $nomina->total_percepciones ?? 0;
        $totalDeducciones = $total_deducciones ?? $nomina->total_deducciones ?? 0;
        $pagoNeto = $pago_neto ?? $nomina->pago_neto ?? 0;

        $fechaInicio = isset($nomina->fecha_inicio)
            ? strtoupper(\Carbon\Carbon::parse($nomina->fecha_inicio)->locale('es')->isoFormat('DD MMMM'))
            : 'N/A';
        $fechaFin = isset($nomina->fecha_fin)
            ? strtoupper(\Carbon\Carbon::parse($nomina->fecha_fin)->locale('es')->isoFormat('DD MMMM YYYY'))
            : 'N/A';

        $cell = 'border: 1px solid #111827; padding: 5px; font-family: Arial; font-size: 11px;';
        $center = $cell . ' text-align: center;';
        $right = $cell . ' text-align: right;';
        $top = $cell . ' vertical-align: top;';
        $topRight = $top . ' text-align: right;';
    @endphp

    <table style="border-collapse: collapse; width: 100%;">
        <tr>
            <td colspan="2" style="border: none;">
                <img src="{{ public_path('img/promatec.png') }}" alt="Promatec" height="48">
                <img src="{{ public_path('img/lugarth.png') }}" alt="Lugarth" height="48">
            </td>
            <td colspan="2" style="border: none; text-align: right; font-family: Arial; font-size: 10px; font-weight: bold;">
                BARRIO DE SANTO TOMAS C.P. 43860<br>PACHUCA DE SOTO, HGO
            </td>
        </tr>

        <tr>
            <td colspan="4" style="{{ $center }} font-size: 16px; font-weight: bold;">
                RECIBO DE SUELDO PACHUCA
            </td>
        </tr>

        <tr>
            <td colspan="2" style="{{ $cell }} height: 58px;">
                <span style="font-size: 10px; font-weight: bold;">Nombre del empleado.</span>
                <span style="color: #008060; font-weight: bold;">{{ $numeroEmpleado }}</span><br><br>
                <div style="text-align: center; font-weight: bold;">{{ strtoupper($empleadoRecibo->nombre_completo ?? 'N/A') }}</div>
            </td>
            <td colspan="2" style="{{ $center }}">
                <span style="color: #0000FF; font-weight: bold;">SEMANA {{ $nomina->numero_semana ?? 'N/A' }}</span><br>
                DEL {{ $fechaInicio }} AL {{ $fechaFin }}
            </td>
        </tr>

        <tr>
            <th colspan="2" style="{{ $center }} font-size: 14px;">PERCEPCIONES</th>
            <th colspan="2" style="{{ $center }} font-size: 14px;">DEDUCCIONES</th>
        </tr>
        <tr>
            <td style="{{ $center }} font-size: 10px; font-weight: bold;">SUELDOS</td>
            <td style="{{ $center }} font-size: 10px; font-weight: bold;">TOTAL</td>
            <td style="{{ $center }} font-size: 10px; font-weight: bold;">CONCEPTO</td>
            <td style="{{ $center }} font-size: 10px; font-weight: bold;">TOTAL</td>
        </tr>
        <tr>
            <td style="{{ $top }} height: 140px;">
                @if($esEstudiante)
                    <b>SUELDO POR HORA</b><br>
                    HORAS NORMALES <span style="color: #0000FF; font-weight: bold;">{{ $horasNormales }}</span><br>
                @else
                    <b>SUELDO DIARIO</b><br>
                    <b>SUELDO SEMANAL</b><br>
                @endif
                HRS EXTRA <span style="color: #0000FF; font-weight: bold;">{{ $horasExtraPagadas }}</span><br>
                @if($horasExtraMiercolesAnterior > 0)
                    MIE. ANT. <span style="color: #0000FF; font-weight: bold;">{{ $horasExtraMiercolesAnterior }}</span><br>
                @endif
                @if($horasAdeudoDescontadas > 0)
                    HRS DESC. <span style="color: #FF0000; font-weight: bold;">{{ $horasAdeudoDescontadas }}</span><br>
                @endif
                COMPENSACION<br>
                INCAP - 60% <span style="color: #0000FF; font-weight: bold;">{{ $diasIncapacidad }}</span><br>
                D.P. VACACION + 25% P.V. <span style="color: #0000FF; font-weight: bold;">{{ $diasVacaciones }}</span>
            </td>
            <td style="{{ $topRight }}">
                @if($esEstudiante)
                    $ {{ number_format($sueldoHoraRecibo, 2) }}<br>
                    $ {{ number_format($pagoNormal, 2) }}<br>
                @else
                    $ {{ number_format($sueldoDiario, 2) }}<br>
                    $ {{ number_format($pagoNormal, 2) }}<br>
                @endif
                $ {{ number_format($pagoExtra, 2) }}<br>
                @if($horasExtraMiercolesAnterior > 0)
                    <span style="font-size: 9px;">Incluido</span><br>
                @endif
                @if($horasAdeudoDescontadas > 0)
                    -<br>
                @endif
                $ {{ number_format($prestamoOtorgado, 2) }}<br>
                $ {{ number_format($pagoIncapacidad, 2) }}<br>
                $ {{ number_format($pagoVacaciones, 2) }}
            </td>
            <td style="{{ $top }}">
                Falta(s) <span style="color: #FF0000; font-weight: bold;">{{ $diasFaltaDescontables }}</span><br>
                Faltas pagadas <span style="color: #0000FF; font-weight: bold;">{{ $diasFaltaPagados }}</span><br>
                Adeudo<br>
                IMSS<br>
                ISR<br>
                INFONAVIT<br>
                Retardo <span style="color: #0000FF; font-weight: bold;">{{ $minutosTardeDescontables }}</span><br>
                Descuento
            </td>
            <td style="{{ $topRight }}">
                $ {{ number_format($descuentoFaltas, 2) }}<br>
                -<br>
                $ {{ number_format($prestamoDescuento, 2) }}<br>
                $ {{ number_format($descuentoImss, 2) }}<br>
                $ {{ number_format($descuentoIsr, 2) }}<br>
                $ {{ number_format($descuentoInfonavit, 2) }}<br>
                $ {{ number_format($descuentoRetardos, 2) }}<br>
                $ {{ number_format($deduccionManual, 2) }}
            </td>
        </tr>

        <tr>
            <td colspan="2" style="{{ $right }} font-weight: bold;">TOTAL DE PERCEPCIONES:</td>
            <td colspan="2" style="{{ $right }} font-weight: bold;">$ {{ number_format($totalPercepciones, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2" style="{{ $right }} font-weight: bold;">TOTAL DE DEDUCCIONES:</td>
            <td colspan="2" style="{{ $right }} font-weight: bold;">$ {{ number_format($totalDeducciones, 2) }}</td>
        </tr>
        <tr>
            <td colspan="2" style="{{ $right }} font-size: 13px; font-weight: bold;">NETO A PAGAR:</td>
            <td colspan="2" style="{{ $right }} font-size: 13px; font-weight: bold;">$ {{ number_format($pagoNeto, 2) }}</td>
        </tr>

        <tr>
            <td colspan="2" style="{{ $cell }} height: 50px; font-size: 8px; text-align: justify; vertical-align: top;">
                Recibi de: PROMATEC, LUGARTH la cantidad anotada en este Recibo de pago de mi sueldo; ademas certifico que no se me adeuda a la fecha cantidad alguna por tiempo extra.
            </td>
            <td colspan="2" style="{{ $center }} vertical-align: bottom;">
                ___________________________________<br>
                Firma del empleado
            </td>
        </tr>
    </table>
</body>
</html>
