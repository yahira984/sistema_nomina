@php
    $empleadoRecibo = $empleado ?? $nomina->empleado ?? null;
    $numeroEmpleado = $empleadoRecibo->numero_empleado ?? 'S/N';
    $nombreEmpleado = strtoupper($empleadoRecibo->nombre_completo ?? 'N/A');
    $esEstudiante = $es_estudiante ?? (bool) ($empleadoRecibo->es_estudiante ?? false);
    $sueldoSemanal = $sueldo_semanal ?? ($empleadoRecibo->sueldo_semanal ?? 0);
    $sueldoHora = $empleadoRecibo->sueldo_por_hora ?? 0;
    $tarifaBaseHora = $tarifa_base_hora ?? ($esEstudiante ? $sueldoHora : ($sueldoSemanal > 0 ? $sueldoSemanal / 48 : 0));
    $horasNormales = $nomina->horas_normales ?? 0;
    $horasExtra = $nomina->horas_extra ?? 0;
    $pagoNormal = $pago_normal ?? 0;
    $pagoExtra = $pago_extra ?? 0;
    $diasIncapacidad = $dias_incapacidad ?? 0;
    $diasVacaciones = $dias_vacaciones ?? 0;
    $diasFalta = $dias_falta ?? 0;
    $minutosTarde = $minutos_tarde_acumulados ?? 0;
    $pagoIncapacidad = $pago_incapacidad ?? 0;
    $pagoVacaciones = $pago_vacaciones ?? 0;
    $descuentoFaltas = $descuento_faltas ?? 0;
    $descuentoRetardos = $descuento_retardos ?? 0;
    $cuotaPrestamo = $deduccion_prestamo ?? ($empleadoRecibo->cuota_prestamo ?? 0);
    $descuentoImss = $empleadoRecibo->descuento_imss ?? 0;
    $descuentoIsr = $empleadoRecibo->descuento_isr ?? 0;
    $descuentoInfonavit = $empleadoRecibo->descuento_infonavit ?? 0;
    $totalPercepciones = $total_percepciones ?? $nomina->total_percepciones ?? 0;
    $totalDeducciones = $total_deducciones ?? $nomina->total_deducciones ?? 0;
    $pagoNeto = $pago_neto ?? $nomina->pago_neto ?? 0;
    $fechaInicio = isset($nomina->fecha_inicio)
        ? strtoupper(\Carbon\Carbon::parse($nomina->fecha_inicio)->locale('es')->isoFormat('DD MMMM'))
        : 'N/A';
    $fechaFin = isset($nomina->fecha_fin)
        ? strtoupper(\Carbon\Carbon::parse($nomina->fecha_fin)->locale('es')->isoFormat('DD MMMM YYYY'))
        : 'N/A';
    $cell = 'border: 1px solid #cbd5e1; padding: 7px; font-family: Arial; font-size: 12px; color: #0f172a;';
    $center = $cell . ' text-align: center;';
    $right = $cell . ' text-align: right;';
    $top = $cell . ' vertical-align: top;';
    $topRight = $top . ' text-align: right;';
    $muted = 'color: #64748b; font-size: 10px;';
    $money = 'color: #0f766e; font-weight: bold;';
@endphp

<table style="border-collapse: collapse; border: 1px solid #cbd5e1;">
    <tr style="height: 62px;">
        <td style="border: none; padding: 12px; vertical-align: middle;">
            <img src="{{ public_path('img/promatec.png') }}" alt="Promatec" height="50">
        </td>
        <td style="border: none; padding: 12px; vertical-align: middle;">
            <img src="{{ public_path('img/lugarth.png') }}" alt="Lugarth" height="50">
        </td>
        <td colspan="2" style="border: none; padding: 12px; color: #475569; font-family: Arial; font-size: 10px; text-align: right; vertical-align: top;">
            BARRIO DE SANTO TOMAS C.P. 43860<br><br>
            PACHUCA DE SOTO, HGO
        </td>
    </tr>

    <tr>
        <td colspan="4" style="{{ $center }} border-top: 1px solid #cbd5e1; background-color: #0f172a; color: #ffffff; font-size: 16px; font-weight: bold; padding: 10px;">
            RECIBO DE SUELDO PACHUCA
        </td>
    </tr>

    <tr style="height: 76px;">
        <td colspan="2" style="{{ $cell }} padding: 15px 5px; vertical-align: middle;">
            <span style="{{ $muted }}">Nombre del empleado</span>
            <span style="{{ $money }}">{{ $numeroEmpleado }}</span><br><br><br>
            <span style="font-weight: bold; text-align: center;">{{ $nombreEmpleado }}</span>
        </td>
        <td colspan="2" style="{{ $center }} padding: 15px 5px;">
            <span style="color: #0f766e; font-weight: bold; font-size: 14px;">SEMANA {{ $nomina->numero_semana ?? 'N/A' }}</span><br><br>
            DEL {{ $fechaInicio }} AL {{ $fechaFin }}
        </td>
    </tr>

    <tr>
        <th colspan="2" style="{{ $center }} background-color: #f1f5f9; font-weight: bold;">PERCEPCIONES</th>
        <th colspan="2" style="{{ $center }} background-color: #f1f5f9; font-weight: bold;">DEDUCCIONES</th>
    </tr>
    <tr>
        <td style="{{ $center }} font-size: 10px;">SUELDOS</td>
        <td style="{{ $center }} font-size: 10px;">TOTAL</td>
        <td colspan="2" style="{{ $center }} font-size: 10px;">TOTAL</td>
    </tr>
    <tr style="height: 140px;">
        <td style="{{ $top }} padding-top: 10px;">
            @if($esEstudiante)
                <b>SUELDO (Tarifa x Hora: ${{ number_format($sueldoHora, 2) }})</b><br>
            @else
                <b>SUELDO SEMANAL BASE: ${{ number_format($sueldoSemanal, 2) }}</b><br>
            @endif
            <br>
            @if($esEstudiante)
                HORAS NORMALES {{ $horasNormales }} hrs<br>
            @else
                HORAS REGISTRADAS {{ $horasNormales }} hrs<br>
            @endif
            @if($horasExtra > 0)
                HORAS EXTRA (Base: ${{ number_format($tarifaBaseHora, 2) }}) {{ $horasExtra }} hrs<br>
            @else
                <br>
            @endif
            <br>
            @if($diasIncapacidad > 0)
                <span style="{{ $money }} font-size: 10px;">INCAPACIDAD ({{ $diasIncapacidad }} Días) al 60%</span><br>
            @endif
            @if($diasVacaciones > 0)
                <span style="font-size: 8px;">D.P. VACACION + 25% P.V. ({{ $diasVacaciones }} Días)</span><br>
            @else
                <span style="font-size: 8px;">D.P. VACACION + 25% P.V.</span><br>
            @endif
        </td>

        <td style="{{ $topRight }} padding-top: 10px;">
            <br>
            <span data-format='"$"#,##0.00'>$ {{ number_format($pagoNormal, 2) }}</span><br>
            @if($horasExtra > 0)
                <span data-format='"$"#,##0.00'>$ {{ number_format($pagoExtra, 2) }}</span><br>
            @else
                <br>
            @endif
            <br>
            @if($diasIncapacidad > 0)
                $ {{ number_format($pagoIncapacidad, 2) }}<br>
            @endif
            @if($diasVacaciones > 0)
                $ {{ number_format($pagoVacaciones, 2) }}
            @else
                <br>
            @endif
        </td>

        <td style="{{ $top }} padding-top: 10px;">
            @if($diasFalta > 0)
                Falta(s) - {{ $diasFalta }} días<br>
            @else
                Falta (s)<br>
            @endif
            P. Personal<br>
            Retardos ({{ $minutosTarde }} min)<br>
            Préstamos<br>
            Seguro / IMSS<br>
            ISR<br>
            INFONAVIT
        </td>
        <td style="{{ $topRight }} padding-top: 10px;">
            $ {{ number_format($descuentoFaltas, 2) }}<br>
            $ 0.00<br>
            $ {{ number_format($descuentoRetardos, 2) }}<br>
            $ {{ number_format($cuotaPrestamo, 2) }}<br>
            $ {{ number_format($descuentoImss, 2) }}<br>
            $ {{ number_format($descuentoIsr, 2) }}<br>
            $ {{ number_format($descuentoInfonavit, 2) }}
        </td>
    </tr>
    <tr>
        <td colspan="2" style="{{ $cell }} font-weight: bold;">
            TOTAL DE PERCEPCIONES: $ {{ number_format($totalPercepciones, 2) }}
        </td>
        <td colspan="2" style="{{ $cell }} font-weight: bold;">
            TOTAL DE DEDUCCIONES: $ {{ number_format($totalDeducciones, 2) }}
        </td>
    </tr>
    <tr>
        <td colspan="4" style="{{ $right }} background-color: #ecfdf5; color: #065f46; font-size: 15px; padding: 10px; font-weight: bold;">
            NETO A PAGAR: $ {{ number_format($pagoNeto, 2) }}
        </td>
    </tr>

    <tr style="height: 70px;">
        <td colspan="2" style="{{ $cell }} color: #334155; font-size: 9px; padding: 12px; text-align: justify; vertical-align: top;">
            Recibí de: PROMATEC, LUGARTH la cantidad anotada en este Recibo de pago de mi sueldo; además<br><br>
            certifico que no se me adeuda a la fecha cantidad alguna por tiempo extra.
        </td>
        <td colspan="2" style="{{ $center }} vertical-align: bottom;">
            ___________________________________<br>
            Firma del empleado
        </td>
    </tr>
</table>
