<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibos de Sueldo</title>
    <style>
        @page {
            size: letter portrait;
            margin: 0.25in;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .recibo-page {
            height: 10.45in;
            page-break-after: always;
        }

        .recibo-page:last-child {
            page-break-after: auto;
        }

        .recibo-slot {
            height: 5.12in;
            box-sizing: border-box;
            overflow: hidden;
        }

        .recibo-slot:first-child {
            margin-bottom: 0.12in;
            padding-bottom: 0.08in;
            border-bottom: 1px dashed #cbd5e1;
        }

        .recibo-slot table {
            width: 100% !important;
        }
    </style>
</head>
<body>
    @foreach(collect($recibos)->chunk(2) as $paginaRecibos)
        <div class="recibo-page">
            @foreach($paginaRecibos as $recibo)
                <div class="recibo-slot">
                    @include('excel.recibo_individual', $recibo)
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
