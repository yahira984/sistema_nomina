<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Throwable;

class BaseDatosController extends Controller
{
    public function index()
    {
        $tablas = $this->obtenerTablas();

        return Inertia::render('Sistema/BaseDatos', [
            'conexion' => config('database.default'),
            'baseDatos' => DB::getDatabaseName(),
            'tablas' => $tablas,
            'totalTablas' => count($tablas),
        ]);
    }

    public function exportar()
    {
        if (DB::getDriverName() !== 'mysql') {
            abort(422, 'La exportacion integrada esta preparada para MySQL.');
        }

        $sql = $this->generarRespaldoSql();
        $nombreBase = preg_replace('/[^A-Za-z0-9_-]/', '_', DB::getDatabaseName() ?: 'base_datos');
        $archivo = 'respaldo_' . $nombreBase . '_' . now()->format('Ymd_His') . '.sql';

        return response($sql, 200, [
            'Content-Type' => 'application/sql; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $archivo . '"',
        ]);
    }

    public function importar(Request $request)
    {
        if (DB::getDriverName() !== 'mysql') {
            return back()->withErrors([
                'archivo_sql' => 'La importacion integrada esta preparada para MySQL.',
            ]);
        }

        $request->validate([
            'archivo_sql' => 'required|file|max:102400',
            'confirmacion' => 'required|in:RESTAURAR',
        ], [
            'archivo_sql.uploaded' => 'El respaldo no pudo subirse. Revisa que upload_max_filesize y post_max_size permitan este tamano.',
            'archivo_sql.max' => 'El respaldo no debe pesar mas de 100 MB.',
            'confirmacion.in' => 'Escribe RESTAURAR para confirmar la importacion.',
        ]);

        $archivoSql = $request->file('archivo_sql');
        $extension = strtolower($archivoSql->getClientOriginalExtension());

        if (!in_array($extension, ['sql', 'txt'], true)) {
            return back()->withErrors([
                'archivo_sql' => 'Selecciona un archivo .sql o .txt generado desde este sistema.',
            ]);
        }

        $contenido = file_get_contents($archivoSql->getRealPath());

        if ($contenido === false) {
            return back()->withErrors([
                'archivo_sql' => 'No se pudo leer el archivo seleccionado.',
            ]);
        }

        if (!str_contains($contenido, '-- Respaldo generado por Sistema de Nominas')) {
            return back()->withErrors([
                'archivo_sql' => 'Por seguridad solo se aceptan respaldos generados desde este sistema.',
            ]);
        }

        $sentencias = $this->dividirSql($contenido);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($sentencias as $sentencia) {
                $sentencia = $this->limpiarSentencia($sentencia);

                if ($sentencia === '') {
                    continue;
                }

                DB::unprepared($sentencia);
            }
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'archivo_sql' => 'No se pudo importar el respaldo. Revisa que el archivo no este danado.',
            ]);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return back()->with('success', 'Base de datos restaurada correctamente.');
    }

    private function generarRespaldoSql(): string
    {
        $pdo = DB::getPdo();
        $baseDatos = DB::getDatabaseName();
        $tablas = $this->obtenerTablas();

        $sql = "-- Respaldo generado por Sistema de Nominas\n";
        $sql .= "-- Base de datos: {$baseDatos}\n";
        $sql .= "-- Fecha: " . now()->format('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tablas as $tabla) {
            $tablaSql = $this->envolverIdentificador($tabla);
            $createRow = DB::select("SHOW CREATE TABLE {$tablaSql}")[0] ?? null;
            $createData = (array) $createRow;
            $createStatement = $createData['Create Table'] ?? array_values($createData)[1] ?? null;

            if (!$createStatement) {
                continue;
            }

            $sql .= "-- --------------------------------------------------------\n";
            $sql .= "-- Tabla: {$tabla}\n";
            $sql .= "-- --------------------------------------------------------\n";
            $sql .= "DROP TABLE IF EXISTS {$tablaSql};\n";
            $sql .= $createStatement . ";\n\n";

            $filas = DB::table($tabla)->get();

            foreach ($filas as $fila) {
                $datos = (array) $fila;
                $columnas = implode(', ', array_map(fn ($columna) => $this->envolverIdentificador($columna), array_keys($datos)));
                $valores = implode(', ', array_map(fn ($valor) => $this->valorSql($valor, $pdo), array_values($datos)));

                $sql .= "INSERT INTO {$tablaSql} ({$columnas}) VALUES ({$valores});\n";
            }

            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }

    private function obtenerTablas(): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return [];
        }

        $filas = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");

        return collect($filas)
            ->map(fn ($fila) => array_values((array) $fila)[0] ?? null)
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    private function envolverIdentificador(string $identificador): string
    {
        return '`' . str_replace('`', '``', $identificador) . '`';
    }

    private function valorSql(mixed $valor, \PDO $pdo): string
    {
        if ($valor === null) {
            return 'NULL';
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        if (is_int($valor) || is_float($valor)) {
            return (string) $valor;
        }

        return $pdo->quote((string) $valor);
    }

    private function dividirSql(string $sql): array
    {
        $sentencias = [];
        $actual = '';
        $comilla = null;
        $escape = false;
        $largo = strlen($sql);

        for ($i = 0; $i < $largo; $i++) {
            $caracter = $sql[$i];

            if ($comilla !== null) {
                $actual .= $caracter;

                if ($escape) {
                    $escape = false;
                    continue;
                }

                if ($caracter === '\\') {
                    $escape = true;
                    continue;
                }

                if ($caracter === $comilla) {
                    $comilla = null;
                }

                continue;
            }

            if ($caracter === "'" || $caracter === '"' || $caracter === '`') {
                $comilla = $caracter;
                $actual .= $caracter;
                continue;
            }

            if ($caracter === ';') {
                $sentencias[] = $actual;
                $actual = '';
                continue;
            }

            $actual .= $caracter;
        }

        if (trim($actual) !== '') {
            $sentencias[] = $actual;
        }

        return $sentencias;
    }

    private function limpiarSentencia(string $sentencia): string
    {
        $lineas = preg_split('/\R/', trim($sentencia)) ?: [];
        $lineas = array_filter($lineas, function ($linea) {
            $limpia = trim($linea);

            return $limpia !== '' && !str_starts_with($limpia, '--');
        });

        return trim(implode("\n", $lineas));
    }
}
