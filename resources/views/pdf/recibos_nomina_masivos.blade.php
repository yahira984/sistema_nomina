<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibos de Sueldo</title>
    <style>
        @page {
            margin: 0.35in 0.25in;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .recibo-page {
            page-break-after: always;
        }

        .recibo-page:last-child {
            page-break-after: auto;
        }

        .recibo-page table {
            width: 100%;
        }
    </style>
</head>
<body>
    @foreach($recibos as $recibo)
        <div class="recibo-page">
            @include('excel.recibo_individual', $recibo)
        </div>
    @endforeach
</body>
</html>
