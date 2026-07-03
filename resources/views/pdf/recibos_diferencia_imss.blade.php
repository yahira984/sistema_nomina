<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibos Diferencia IMSS</title>
    <style>
        @page {
            size: letter portrait;
            margin: 0.15in 0.25in;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .recibo-page {
            height: 10.7in;
            page-break-after: always;
        }

        .recibo-page:last-child {
            page-break-after: auto;
        }

        .recibo-slot {
            height: 5.08in;
            box-sizing: border-box;
            overflow: hidden;
        }

        .recibo-slot:first-child {
            margin-bottom: 0.12in;
            border-bottom: 1px dashed #cbd5e1;
        }

        .recibo-slot table {
            width: 100% !important;
        }

        .empty {
            padding: 1in 0;
            color: #64748b;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: 800;
            text-align: center;
        }
    </style>
</head>
<body>
    @if(collect($recibos)->isEmpty())
        <div class="empty">No hay diferencias IMSS registradas para este periodo.</div>
    @else
        @foreach(collect($recibos)->chunk(2) as $paginaRecibos)
            <div class="recibo-page">
                @foreach($paginaRecibos as $recibo)
                    <div class="recibo-slot">
                        @include('pdf._recibo_diferencia_imss', ['recibo' => $recibo])
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif
</body>
</html>
