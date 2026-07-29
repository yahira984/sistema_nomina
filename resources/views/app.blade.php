<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name') === 'Laravel' ? 'Sistema de Nóminas' : config('app.name', 'Sistema de Nóminas') }}</title>

        <!-- Los recursos críticos se sirven localmente para funcionar sin internet. -->
        <link rel="stylesheet" href="/vendor/tabler-icons/tabler-icons.min.css">

        <!-- Scripts -->
        @routes
        @vite('resources/js/app.js')
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
