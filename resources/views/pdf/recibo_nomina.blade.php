<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Sueldo</title>
    <style>
        body { color: #0f172a; font-family: Arial, sans-serif; font-size: 12px; }
        .table-main { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #cbd5e1; }
        .table-main th, .table-main td { border: 1px solid #cbd5e1; padding: 7px; text-align: left; }
        .header-title { background: #0f172a; color: #ffffff; text-align: center; font-size: 16px; font-weight: bold; letter-spacing: 0; padding: 10px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .semana-info { color: #0f766e; font-weight: bold; font-size: 14px; }
        .no-border { border: none !important; }
        .logos-container { padding: 12px; }
        .logos-container img { height: 50px; margin-right: 20px; vertical-align: middle; }
        .address-text { color: #475569; font-size: 10px; text-align: right; padding: 12px; vertical-align: top; }
        .muted { color: #64748b; font-size: 10px; }
        .section-band { background-color: #f1f5f9; color: #0f172a; font-weight: bold; }
        .money { color: #0f766e; font-weight: bold; }
        .net-total { background: #ecfdf5; color: #065f46; font-size: 15px; padding: 10px; }
        .signature-copy { color: #334155; font-size: 9px; padding: 12px; text-align: justify; }
    </style>
</head>
<body>

    <table class="table-main">
        <tr>
            <td colspan="2" class="no-border logos-container">
                <img src="{{ public_path('img/promatec.png') }}" alt="Promatec">
                <img src="{{ public_path('img/lugarth.png') }}" alt="Lugarth">
            </td>
            <td colspan="2" class="no-border address-text">
                BARRIO DE SANTO TOMAS C.P. 43860<br><br>
                PACHUCA DE SOTO, HGO
            </td>
        </tr>
        
        <tr>
            <td colspan="4" class="header-title" style="border-top: 1px solid #cbd5e1;">
                RECIBO DE SUELDO PACHUCA
            </td>
        </tr>

        <tr>
            <td colspan="2" style="padding: 15px 5px;">
                <span class="muted">Nombre del empleado</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <span class="money">{{ $nomina->empleado->numero_empleado ?? 'S/N' }}</span> <br><br><br>
                <div class="center" style="font-weight: bold;">{{ strtoupper($nomina->empleado->nombre_completo) }}</div>
            </td>
            <td colspan="2" class="center" style="padding: 15px 5px;">
                <span class="semana-info">SEMANA {{ $nomina->numero_semana }}</span><br><br>
                DEL {{ strtoupper(\Carbon\Carbon::parse($nomina->fecha_inicio)->locale('es')->isoFormat('DD MMMM')) }} 
                AL {{ strtoupper(\Carbon\Carbon::parse($nomina->fecha_fin)->locale('es')->isoFormat('DD MMMM YYYY')) }}
            </td>
        </tr>

        <tr>
            <th colspan="2" class="center section-band">PERCEPCIONES</th>
            <th colspan="2" class="center section-band">DEDUCCIONES</th>
        </tr>
        <tr>
            <td class="center" style="width: 25%; font-size: 10px;">SUELDOS</td>
            <td class="center" style="width: 25%; font-size: 10px;">TOTAL</td>
            <td colspan="2" class="center" style="font-size: 10px;">TOTAL</td>
        </tr>
        <tr>
            <td style="vertical-align: top; padding-top: 10px;">
                @if($nomina->empleado->sueldo_por_hora > 0)
                    <b>SUELDO (Tarifa x Hora: ${{ number_format($nomina->empleado->sueldo_por_hora, 2) }})</b><br>
                @else
                    <b>SUELDO BASE</b><br>
                @endif
                <br>
                
                HORAS NORMALES <span style="float:right;">{{ $nomina->horas_normales }} hrs</span><br>
                
                @if($nomina->horas_extra > 0)
                HORAS EXTRA <span style="float:right;">{{ $nomina->horas_extra }} hrs</span><br>
                @else
                <br>
                @endif
                <br>
                
                @if(isset($dias_incapacidad) && $dias_incapacidad > 0)
                <span class="money" style="font-size: 10px;">INCAPACIDAD ({{ $dias_incapacidad }} Días) al 60%</span><br>
                @endif
                
                @if(isset($dias_vacaciones) && $dias_vacaciones > 0)
                <span style="font-size: 8px;">D.P. VACACION + 25% P.V. ({{ $dias_vacaciones }} Días)</span><br>
                @else
                <span style="font-size: 8px;">D.P. VACACION + 25% P.V.</span><br>
                @endif
            </td>
            
            <td style="vertical-align: top; padding-top: 10px;" class="right">
                <br>
                <!-- AHORA SÍ IMPRIME EL DINERO PERFECTO -->
                $ {{ number_format($pago_normal, 2) }}<br>
                
                @if($nomina->horas_extra > 0)
                $ {{ number_format($pago_extra, 2) }}<br>
                @else
                <br>
                @endif
                <br>
                
                @if(isset($dias_incapacidad) && $dias_incapacidad > 0)
                $ {{ number_format($pago_incapacidad, 2) }}<br>
                @endif
                
                @if(isset($dias_vacaciones) && $dias_vacaciones > 0)
                $ {{ number_format($pago_vacaciones, 2) }}
                @else
                <br>
                @endif
            </td>
            
            <td style="vertical-align: top; padding-top: 10px; width: 25%;">
                @if(isset($dias_falta) && $dias_falta > 0)
                Falta(s) - {{ $dias_falta }} días<br>
                @else
                Falta (s)<br>
                @endif
                P. Personal<br>
                Retardos ({{ $minutos_tarde_acumulados ?? 0 }} min)<br>
                Préstamos<br>
                Seguro / IMSS<br>
                ISR<br>
                INFONAVIT
            </td>
            <td style="vertical-align: top; padding-top: 10px; width: 25%;" class="right">
                $ {{ number_format($descuento_faltas ?? 0, 2) }} <br>
                $ 0.00<br>
                $ {{ number_format($descuento_retardos ?? 0, 2) }} <br>
                $ {{ number_format($nomina->empleado->cuota_prestamo, 2) }} <br>
                $ {{ number_format($nomina->empleado->descuento_imss, 2) }} <br>
                $ {{ number_format($nomina->empleado->descuento_isr, 2) }} <br>
                $ {{ number_format($nomina->empleado->descuento_infonavit, 2) }}
            </td>
        </tr>
        <tr>
            <td colspan="2">TOTAL DE PERCEPCIONES: <span style="float:right">$ {{ number_format($nomina->total_percepciones, 2) }}</span></td>
            <td colspan="2">TOTAL DE DEDUCCIONES: <span style="float:right">$ {{ number_format($nomina->total_deducciones, 2) }}</span></td>
        </tr>
        <tr>
            <td colspan="4" class="right net-total"><b>NETO A PAGAR: $ {{ number_format($nomina->pago_neto, 2) }}</b></td>
        </tr>

        <tr>
            <td colspan="2" class="signature-copy">
                Recibí de: PROMATEC, LUGARTH la cantidad anotada en este Recibo de pago de mi sueldo; además<br><br>
                certifico que no se me adeuda a la fecha cantidad alguna por tiempo extra.
            </td>
            <td colspan="2" class="center" style="vertical-align: bottom;">
                ___________________________________<br>
                Firma del empleado
            </td>
        </tr>
    </table>

</body>
</html>