@php
    $nomina = $recibo['nomina'];
    $empleado = $recibo['empleado'];
    $diferencia = (float) $recibo['diferencia_imss'];
    $numeroEmpleado = $empleado->numero_empleado ?? $empleado->numero_empleado_baja ?? 'S/N';
    $nombreEmpleado = strtoupper($empleado->nombre_completo ?? 'N/A');
    $fechaInicio = strtoupper(\Carbon\Carbon::parse($nomina->fecha_inicio)->locale('es')->isoFormat('DD MMMM'));
    $fechaFin = strtoupper(\Carbon\Carbon::parse($nomina->fecha_fin)->locale('es')->isoFormat('DD MMMM YYYY'));
    $cell = 'border: 1px solid #111827; padding: 5px; font-family: Arial; font-size: 11px; color: #111827;';
    $center = $cell . ' text-align: center;';
    $right = $cell . ' text-align: right;';
    $top = $cell . ' vertical-align: top;';
    $topRight = $top . ' text-align: right;';
    $muted = 'color: #111827; font-size: 10px; font-weight: bold;';
    $green = 'color: #00A651; font-weight: bold;';
    $blue = 'color: #0000FF; font-weight: bold;';
    $red = 'color: #FF0000; font-weight: bold;';
@endphp

<table style="border-collapse: collapse; width: 100%;">
    <tr>
        <td colspan="2" style="border: none;">
            <img src="{{ public_path('img/promatec.png') }}" alt="Promatec" height="42">
            <img src="{{ public_path('img/lugarth.png') }}" alt="Lugarth" height="42">
        </td>
        <td colspan="2" style="border: none; text-align: right; font-family: Arial; font-size: 10px; font-weight: bold;">
            BARRIO DE SANTO TOMAS C.P. 43860<br>
            PACHUCA DE SOTO, HGO
        </td>
    </tr>

    <tr>
        <td colspan="4" style="{{ $center }} font-size: 16px; font-weight: bold;">
            RECIBO DE SUELDO PACHUCA
        </td>
    </tr>

    <tr>
        <td colspan="2" style="{{ $cell }} height: 50px;">
            <span style="{{ $muted }}">Nombre del empleado.&nbsp;</span>
            <span style="{{ $green }}">{{ $numeroEmpleado }}</span><br><br>
            <div style="text-align: center; font-weight: bold;">{{ $nombreEmpleado }}</div>
        </td>
        <td colspan="2" style="{{ $center }}">
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
    <tr>
        <td style="{{ $top }} height: 122px;">
            <b>SUELDO</b><br>
        </td>
        <td style="{{ $topRight }}">
            $ {{ number_format($diferencia, 2) }}<br>
        </td>
        <td style="{{ $top }}">
            Falta(s)&nbsp;<span style="{{ $red }}">0</span><br>
            Adeudo<br>
            IMSS<br>
            ISR<br>
            INFONAVIT<br>
            Retardo&nbsp;<span style="{{ $blue }}">0</span><br>
            Descuento
        </td>
        <td style="{{ $topRight }}">
            $ {{ number_format(0, 2) }}<br>
            $ {{ number_format(0, 2) }}<br>
            $ {{ number_format(0, 2) }}<br>
            $ {{ number_format(0, 2) }}<br>
            $ {{ number_format(0, 2) }}<br>
            $ {{ number_format(0, 2) }}<br>
            $ {{ number_format(0, 2) }}
        </td>
    </tr>

    <tr>
        <td colspan="2" style="{{ $right }} font-weight: bold;">TOTAL DE PERCEPCIONES:</td>
        <td colspan="2" style="{{ $right }} font-weight: bold;">$ {{ number_format($diferencia, 2) }}</td>
    </tr>
    <tr>
        <td colspan="2" style="{{ $right }} font-weight: bold;">TOTAL DE DEDUCCIONES:</td>
        <td colspan="2" style="{{ $right }} font-weight: bold;">$ {{ number_format(0, 2) }}</td>
    </tr>
    <tr>
        <td colspan="2" style="{{ $right }} font-weight: bold; font-size: 13px;">NETO A PAGAR:</td>
        <td colspan="2" style="{{ $right }} font-weight: bold; font-size: 13px;">$ {{ number_format($diferencia, 2) }}</td>
    </tr>

    <tr>
        <td colspan="2" style="{{ $cell }} height: 52px; font-size: 8px; text-align: justify; vertical-align: top;">
            Recibi de: PROMATEC, LUGARTH la cantidad anotada en este Recibo de pago de mi sueldo; ademas certifico que no se me adeuda a la fecha cantidad alguna por tiempo extra.
        </td>
        <td colspan="2" style="{{ $center }} vertical-align: bottom;">
            ___________________________________<br>
            Firma del empleado
        </td>
    </tr>
</table>
