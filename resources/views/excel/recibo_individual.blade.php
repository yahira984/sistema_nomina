@php
    $empleadoRecibo = $empleado ?? $nomina->empleado ?? null;
    $numeroEmpleado = $empleadoRecibo->numero_empleado ?? $empleadoRecibo->numero_empleado_baja ?? 'S/N';
    $nombreEmpleado = strtoupper($empleadoRecibo->nombre_completo ?? 'N/A');
    $esEstudiante = $es_estudiante ?? (bool) ($empleadoRecibo->es_estudiante ?? false);
    $sueldoSemanal = $sueldo_semanal ?? ($empleadoRecibo->sueldo_semanal ?? 0);
    $sueldoHora = $sueldo_por_hora ?? ($empleadoRecibo->sueldo_por_hora ?? 0);
    $sueldoDiario = $pago_dia_planta ?? ($sueldoSemanal > 0 ? $sueldoSemanal / 7 : 0);
    $tarifaBaseHora = $tarifa_base_hora ?? ($esEstudiante ? $sueldoHora : ($sueldoSemanal > 0 ? $sueldoSemanal / 56 : 0));
    $horasNormales = $nomina->horas_normales ?? 0;
    $horasExtra = $nomina->horas_extra ?? 0;
    $horasExtraPeriodo = $horas_extra_periodo ?? $horasExtra;
    $horasExtraMiercolesAnterior = $horas_extra_miercoles_anterior ?? 0;
    $horasExtraPagadas = $horas_extra_pagadas ?? ($nomina->horas_extra_pagadas ?? $horasExtra);
    $horasAdeudoDescontadas = $horas_adeudo_descontadas ?? ($nomina->horas_adeudo_descontadas ?? 0);
    $pagoNormal = $pago_normal ?? 0;
    $pagoExtra = $pago_extra ?? 0;
    $diasIncapacidad = $dias_incapacidad ?? 0;
    $diasVacaciones = $dias_vacaciones_pagadas ?? $dias_vacaciones ?? ($nomina->dias_vacaciones_pagadas ?? 0);
    $diasFalta = $dias_falta ?? 0;
    $diasFaltaPagados = $dias_falta_pagados ?? ($nomina->faltas_pagadas ?? 0);
    $diasFaltaDescontables = $dias_falta_descontables ?? max(0, $diasFalta - $diasFaltaPagados);
    $minutosTarde = $minutos_tarde_acumulados ?? 0;
    $minutosTardeDescontables = $minutos_tarde_descontables ?? $minutosTarde;
    $pagoIncapacidad = $pago_incapacidad ?? 0;
    $pagoVacaciones = $pago_vacaciones ?? 0;
    $prestamoOtorgado = $prestamo_otorgado ?? ($nomina->prestamo_otorgado ?? 0);
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
    $cell = 'border: 1px solid #111827; padding: 5px; font-family: Arial; font-size: 11px; color: #111827;';
    $center = $cell . ' text-align: center;';
    $right = $cell . ' text-align: right;';
    $top = $cell . ' vertical-align: top;';
    $topRight = $top . ' text-align: right;';
    $muted = 'color: #111827; font-size: 10px; font-weight: bold;';
    $green = 'color: #00A651; font-weight: bold;';
    $blue = 'color: #0000FF; font-weight: bold;';
    $red = 'color: #FF0000; font-weight: bold;';
    $imagenesPdfBase64 = $imagenes_pdf_base64 ?? false;
    $logoSrc = function (string $archivo) use ($imagenesPdfBase64) {
        $archivoPdf = [
            'promatec.png' => 'promatec-pdf.jpg',
            'lugarth.png' => 'lugarth-pdf.jpg',
        ][$archivo] ?? $archivo;
        $path = public_path('img/' . ($imagenesPdfBase64 ? $archivoPdf : $archivo));

        if (!$imagenesPdfBase64 || !is_file($path)) {
            return $path;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    };
    $logoPromatec = $logoSrc('promatec.png');
    $logoLugarth = $logoSrc('lugarth.png');
@endphp

<table style="border-collapse: collapse; border: 2px solid #111827;">
    <tr style="height: 48px;">
        <td style="border: none; padding: 8px; vertical-align: middle;">
            <img src="{{ $logoPromatec }}" alt="Promatec" height="42">
        </td>
        <td style="border: none; padding: 8px; vertical-align: middle;">
            <img src="{{ $logoLugarth }}" alt="Lugarth" height="42">
        </td>
        <td colspan="2" style="border: none; padding: 8px; font-family: Arial; font-size: 10px; text-align: right; vertical-align: top; font-weight: bold;">
            BARRIO DE SANTO TOMAS C.P. 43860<br>
            PACHUCA DE SOTO, HGO
        </td>
    </tr>

    <tr>
        <td colspan="4" style="{{ $center }} font-size: 16px; font-weight: bold;">
            RECIBO DE SUELDO PACHUCA
        </td>
    </tr>

    <tr style="height: 66px;">
        <td colspan="2" style="{{ $cell }} vertical-align: middle;">
            <span style="{{ $muted }}">Nombre del empleado.&nbsp;</span>
            <span style="{{ $green }}">{{ $numeroEmpleado }}</span><br><br>
            <span style="display: block; text-align: center; font-weight: bold;">{{ $nombreEmpleado }}</span>
        </td>
        <td colspan="2" style="{{ $center }} vertical-align: middle;">
            <span style="{{ $blue }}">SEMANA {{ $nomina->numero_semana ?? 'N/A' }}</span><br>
            DEL {{ $fechaInicio }} AL {{ $fechaFin }}
        </td>
    </tr>

    <tr>
        <th colspan="2" style="{{ $center }} font-size: 14px; font-weight: bold;">PERCEPCIONES</th>
        <th colspan="2" style="{{ $center }} font-size: 14px; font-weight: bold;">DEDUCCIONES</th>
    </tr>
    <tr>
        <td style="{{ $center }} font-size: 10px; font-weight: bold;">SUELDOS</td>
        <td style="{{ $center }} font-size: 10px; font-weight: bold;">TOTAL</td>
        <td style="{{ $center }} font-size: 10px; font-weight: bold;">CONCEPTO</td>
        <td style="{{ $center }} font-size: 10px; font-weight: bold;">TOTAL</td>
    </tr>
    <tr style="height: 150px;">
        <td style="{{ $top }}">
            @if($esEstudiante)
                <b>SUELDO POR HORA</b><br>
                HORAS NORMALES&nbsp;<span style="{{ $blue }}">{{ $horasNormales }}</span><br>
            @else
                <b>SUELDO DIARIO</b><br>
                <b>SUELDO SEMANAL</b><br>
            @endif
            HRS EXTRA&nbsp;<span style="{{ $blue }}">{{ $horasExtraPagadas }}</span><br>
            @if($horasExtraMiercolesAnterior > 0)
                MIE. ANT.&nbsp;<span style="{{ $blue }}">{{ $horasExtraMiercolesAnterior }}</span><br>
            @endif
            @if($horasAdeudoDescontadas > 0)
                HRS DESC.&nbsp;<span style="{{ $red }}">{{ $horasAdeudoDescontadas }}</span><br>
            @endif
            @if($diasFaltaPagados > 0)
                FALTAS PAGADAS&nbsp;<span style="{{ $blue }}">{{ $diasFaltaPagados }}</span><br>
            @endif
            COMPENSACION<br>
            INCAP - 60%&nbsp;<span style="{{ $blue }}">{{ $diasIncapacidad }}</span><br>
            D.P. VACACION + 25% P.V.&nbsp;<span style="{{ $blue }}">{{ $diasVacaciones }}</span>
        </td>

        <td style="{{ $topRight }}">
            @if($esEstudiante)
                $ {{ number_format($sueldoHora, 2) }}<br>
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
            @if($diasFaltaPagados > 0)
                -<br>
            @endif
            $ {{ number_format($prestamoOtorgado, 2) }}<br>
            $ {{ number_format($pagoIncapacidad, 2) }}<br>
            $ {{ number_format($pagoVacaciones, 2) }}
        </td>

        <td style="{{ $top }}">
            Falta(s)&nbsp;<span style="{{ $red }}">{{ $diasFaltaDescontables }}</span><br>
            Adeudo<br>
            IMSS<br>
            ISR<br>
            INFONAVIT<br>
            Retardo&nbsp;<span style="{{ $blue }}">{{ $minutosTardeDescontables }}</span><br>
            Descuento
        </td>
        <td style="{{ $topRight }}">
            $ {{ number_format($descuentoFaltas, 2) }}<br>
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
        <td colspan="2" style="{{ $right }} font-weight: bold; font-size: 13px;">NETO A PAGAR:</td>
        <td colspan="2" style="{{ $right }} font-weight: bold; font-size: 13px;">$ {{ number_format($pagoNeto, 2) }}</td>
    </tr>

    <tr style="height: 72px;">
        <td colspan="2" style="{{ $cell }} font-size: 8px; text-align: justify; vertical-align: top;">
            Recibi de: PROMATEC, LUGARTH la cantidad anotada en este Recibo de pago de mi sueldo; ademas certifico que no se me adeuda a la fecha cantidad alguna por tiempo extra.
        </td>
        <td colspan="2" style="{{ $center }} vertical-align: bottom;">
            ___________________________________<br>
            Firma del empleado
        </td>
    </tr>
</table>
