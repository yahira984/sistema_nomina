<?php

namespace App\Services;

use App\Models\SystemBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;
use ZipArchive;

class DatabaseBackupService
{
    public function createAutomatic(): SystemBackup
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('Los respaldos automáticos están preparados para MySQL.');
        }

        $sql = $this->generateSql();
        $database = preg_replace('/[^A-Za-z0-9_-]/', '_', DB::getDatabaseName() ?: 'database');
        $fileName = 'backup_integral_' . $database . '_' . now()->format('Ymd_His') . '.zip';
        $path = 'backups/automatic/' . $fileName;
        $disk = Storage::disk('local');
        $disk->makeDirectory('backups/automatic');
        $absolutePath = $disk->path($path);
        $zip = new ZipArchive();

        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No fue posible crear el paquete de respaldo integral.');
        }

        try {
            $zip->addFromString('database.sql', $sql);
            $documentCount = $this->addDirectoryToZip(
                $zip,
                $disk->path('employees'),
                'employee-documents'
            );
            $photoCount = $this->addDirectoryToZip(
                $zip,
                public_path('img/empleados'),
                'employee-photos'
            );
            $zip->addFromString('manifest.json', json_encode([
                'format' => 'nominas-integral-backup-v1',
                'created_at' => now()->toISOString(),
                'database' => DB::getDatabaseName(),
                'database_checksum' => hash('sha256', $sql),
                'employee_documents' => $documentCount,
                'employee_photos' => $photoCount,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        } finally {
            $zip->close();
        }

        if (!is_file($absolutePath)) {
            throw new RuntimeException('El paquete de respaldo no se guardó correctamente.');
        }

        $attributes = [
            'disk' => 'local',
            'path' => $path,
            'size_bytes' => filesize($absolutePath) ?: 0,
            'checksum' => hash_file('sha256', $absolutePath),
            'status' => 'created',
        ];

        return Schema::hasTable('system_backups')
            ? SystemBackup::create($attributes)
            : new SystemBackup($attributes);
    }

    public function verify(SystemBackup $backup): SystemBackup
    {
        if (!Storage::disk($backup->disk)->exists($backup->path)) {
            return $this->markVerification($backup, false, 'El archivo ya no existe en almacenamiento.');
        }

        $absolutePath = Storage::disk($backup->disk)->path($backup->path);
        $isArchive = strtolower(pathinfo($backup->path, PATHINFO_EXTENSION)) === 'zip';
        $sql = $this->sqlFromPath($absolutePath, $isArchive ? 'zip' : 'sql');
        $actualChecksum = $isArchive ? hash_file('sha256', $absolutePath) : hash('sha256', $sql);
        $checksumValid = hash_equals((string) $backup->checksum, (string) $actualChecksum);
        $hasHeader = str_contains($sql, '-- Respaldo generado por Sistema de Nominas');
        $requiredTables = collect(['empleados', 'asistencias', 'nominas', 'users'])
            ->every(fn (string $table) => str_contains($sql, "CREATE TABLE `{$table}`"));
        $hasClosing = str_contains($sql, 'SET FOREIGN_KEY_CHECKS=1;');
        $archiveValid = !$isArchive || $this->archiveManifestIsValid($absolutePath, $sql);
        $valid = $checksumValid && $hasHeader && $requiredTables && $hasClosing && $archiveValid;

        return $this->markVerification(
            $backup,
            $valid,
            $valid
                ? ($isArchive
                    ? 'Base de datos, documentos y fotografías verificados dentro del respaldo integral.'
                    : 'Integridad, estructura crítica y cierre SQL verificados.')
                : 'La verificación estructural falló; genera un respaldo nuevo.'
        );
    }

    public function verifyLatest(): ?SystemBackup
    {
        $backup = SystemBackup::latest()->first();

        return $backup ? $this->verify($backup) : null;
    }

    public function testLatestRestore(): ?SystemBackup
    {
        $backup = SystemBackup::latest()->first();

        return $backup ? $this->testRestore($backup) : null;
    }

    public function testRestore(SystemBackup $backup): SystemBackup
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('La prueba aislada de restauración requiere MySQL.');
        }

        if (!Storage::disk($backup->disk)->exists($backup->path)) {
            return $this->markVerification($backup, false, 'No fue posible probar la restauración: el archivo no existe.');
        }

        $connectionName = (string) config('database.default');
        $connectionConfig = (array) config("database.connections.{$connectionName}");
        $temporaryDatabase = 'nominas_restore_test_' . now()->format('Ymd_His') . '_' . bin2hex(random_bytes(2));
        $temporaryConnection = 'system_restore_test';
        $quotedDatabase = $this->quoteIdentifier($temporaryDatabase);

        try {
            DB::statement("CREATE DATABASE {$quotedDatabase} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            config(["database.connections.{$temporaryConnection}" => array_merge(
                $connectionConfig,
                ['database' => $temporaryDatabase]
            )]);
            DB::purge($temporaryConnection);
            $connection = DB::connection($temporaryConnection);

            $sql = $this->sqlFromPath(
                Storage::disk($backup->disk)->path($backup->path),
                strtolower(pathinfo($backup->path, PATHINFO_EXTENSION))
            );

            foreach ($this->splitSql($sql) as $statement) {
                $clean = $this->cleanStatement($statement);
                if ($clean !== '') {
                    $connection->unprepared($clean);
                }
            }

            $schema = Schema::connection($temporaryConnection);
            $required = ['empleados', 'asistencias', 'nominas', 'users'];
            $missing = collect($required)->reject(fn (string $table) => $schema->hasTable($table))->values();

            if ($missing->isNotEmpty()) {
                throw new RuntimeException('Faltaron tablas críticas: ' . $missing->implode(', '));
            }

            $counts = collect($required)
                ->mapWithKeys(fn (string $table) => [$table => $connection->table($table)->count()])
                ->map(fn ($count, $table) => "{$table}: {$count}")
                ->implode(', ');

            return $this->markVerification(
                $backup,
                true,
                "Restauración aislada completada y validada ({$counts})."
            );
        } catch (Throwable $exception) {
            $backup->forceFill([
                'status' => 'restore_test_failed',
                'verified_at' => now(),
                'verification_message' => 'Falló la prueba aislada: ' . $exception->getMessage(),
            ])->save();

            return $backup->fresh();
        } finally {
            DB::disconnect($temporaryConnection);
            DB::purge($temporaryConnection);
            try {
                DB::statement("DROP DATABASE IF EXISTS {$quotedDatabase}");
            } catch (Throwable) {
                // El panel de salud conservará el fallo para revisión manual.
            }
        }
    }

    public function cleanup(int $keepDays = 30): int
    {
        $expired = SystemBackup::where('created_at', '<', now()->subDays(max(7, $keepDays)))->get();
        $deleted = 0;

        foreach ($expired as $backup) {
            Storage::disk($backup->disk)->delete($backup->path);
            $backup->delete();
            $deleted++;
        }

        return $deleted;
    }

    public function generateSql(): string
    {
        $pdo = DB::getPdo();
        $database = DB::getDatabaseName();
        $tables = $this->tables();
        $sql = "-- Respaldo generado por Sistema de Nominas\n";
        $sql .= "-- Base de datos: {$database}\n";
        $sql .= "-- Fecha: " . now()->format('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $quoted = $this->quoteIdentifier($table);
            $createRow = DB::select("SHOW CREATE TABLE {$quoted}")[0] ?? null;
            $createData = (array) $createRow;
            $statement = $createData['Create Table'] ?? array_values($createData)[1] ?? null;

            if (!$statement) {
                continue;
            }

            $sql .= "-- Tabla: {$table}\n";
            $sql .= "DROP TABLE IF EXISTS {$quoted};\n";
            $sql .= $statement . ";\n\n";

            DB::table($table)->orderBy($this->primaryKey($table))->chunk(500, function ($rows) use (&$sql, $table, $quoted, $pdo) {
                foreach ($rows as $row) {
                    $data = (array) $row;
                    $columns = implode(', ', array_map(fn ($column) => $this->quoteIdentifier($column), array_keys($data)));
                    $values = implode(', ', array_map(fn ($value) => $this->sqlValue($value, $pdo), array_values($data)));
                    $sql .= "INSERT INTO {$quoted} ({$columns}) VALUES ({$values});\n";
                }
            });

            $sql .= "\n";
        }

        return $sql . "SET FOREIGN_KEY_CHECKS=1;\n";
    }

    public function sqlFromPath(string $path, string $extension): string
    {
        if (strtolower($extension) !== 'zip') {
            $sql = file_get_contents($path);
            if ($sql === false) {
                throw new RuntimeException('No se pudo leer el respaldo seleccionado.');
            }

            return $sql;
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('El paquete ZIP está dañado o no se puede abrir.');
        }

        try {
            $sql = $zip->getFromName('database.sql');
            if (!is_string($sql) || $sql === '') {
                throw new RuntimeException('El paquete no contiene database.sql.');
            }

            return $sql;
        } finally {
            $zip->close();
        }
    }

    public function restoreAssetsFromArchive(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('No fue posible abrir los archivos del respaldo integral.');
        }

        $restored = ['documents' => 0, 'photos' => 0];

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = str_replace('\\', '/', (string) $zip->getNameIndex($index));
                if ($entry === '' || str_ends_with($entry, '/') || str_contains($entry, '../')) {
                    continue;
                }

                if (str_starts_with($entry, 'employee-documents/')) {
                    $relative = substr($entry, strlen('employee-documents/'));
                    if (!$this->isSafeRelativePath($relative)) {
                        continue;
                    }
                    $stream = $zip->getStream($entry);
                    if (is_resource($stream)) {
                        Storage::disk('local')->put('employees/'.$relative, $stream);
                        if (is_resource($stream)) fclose($stream);
                        $restored['documents']++;
                    }
                } elseif (str_starts_with($entry, 'employee-photos/')) {
                    $relative = substr($entry, strlen('employee-photos/'));
                    if (!$this->isSafeRelativePath($relative)) {
                        continue;
                    }
                    $target = public_path('img/empleados/'.str_replace('/', DIRECTORY_SEPARATOR, $relative));
                    if (!is_dir(dirname($target))) {
                        mkdir(dirname($target), 0775, true);
                    }
                    $source = $zip->getStream($entry);
                    $destination = fopen($target, 'wb');
                    if (is_resource($source) && is_resource($destination)) {
                        stream_copy_to_stream($source, $destination);
                        $restored['photos']++;
                    }
                    if (is_resource($source)) fclose($source);
                    if (is_resource($destination)) fclose($destination);
                }
            }
        } finally {
            $zip->close();
        }

        return $restored;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $source, string $prefix): int
    {
        if (!is_dir($source)) {
            return 0;
        }

        $count = 0;
        $source = rtrim($source, '\\/');
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $relative = ltrim(substr($file->getPathname(), strlen($source)), '\\/');
            $entry = trim($prefix, '/').'/'.str_replace('\\', '/', $relative);
            if ($zip->addFile($file->getPathname(), $entry)) {
                $count++;
            }
        }

        return $count;
    }

    private function archiveManifestIsValid(string $path, string $sql): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return false;
        }

        try {
            $manifestJson = $zip->getFromName('manifest.json');
            if (!is_string($manifestJson)) {
                return false;
            }
            $manifest = json_decode($manifestJson, true, flags: JSON_THROW_ON_ERROR);

            return ($manifest['format'] ?? null) === 'nominas-integral-backup-v1'
                && hash_equals((string) ($manifest['database_checksum'] ?? ''), hash('sha256', $sql));
        } catch (Throwable) {
            return false;
        } finally {
            $zip->close();
        }
    }

    private function isSafeRelativePath(string $path): bool
    {
        return $path !== ''
            && !str_contains($path, "\0")
            && !str_contains(str_replace('\\', '/', $path), '../')
            && !str_starts_with($path, '/')
            && !preg_match('/^[A-Za-z]:/', $path);
    }

    private function tables(): array
    {
        return collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
            ->map(fn ($row) => array_values((array) $row)[0] ?? null)
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    private function primaryKey(string $table): string
    {
        $columns = DB::select("SHOW KEYS FROM {$this->quoteIdentifier($table)} WHERE Key_name = 'PRIMARY'");

        return $columns[0]->Column_name ?? array_key_first((array) DB::table($table)->first()) ?? 'id';
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function sqlValue($value, \PDO $pdo): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $pdo->quote((string) $value);
    }

    private function splitSql(string $sql): array
    {
        $statements = [];
        $current = '';
        $quote = null;
        $escaped = false;

        for ($index = 0, $length = strlen($sql); $index < $length; $index++) {
            $character = $sql[$index];

            if ($quote !== null) {
                $current .= $character;
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }
                continue;
            }

            if (in_array($character, ["'", '"', '`'], true)) {
                $quote = $character;
                $current .= $character;
            } elseif ($character === ';') {
                $statements[] = $current;
                $current = '';
            } else {
                $current .= $character;
            }
        }

        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return $statements;
    }

    private function cleanStatement(string $statement): string
    {
        $lines = preg_split('/\R/', trim($statement)) ?: [];
        $lines = array_filter($lines, fn ($line) => trim($line) !== '' && !str_starts_with(trim($line), '--'));

        return trim(implode("\n", $lines));
    }

    private function markVerification(SystemBackup $backup, bool $valid, string $message): SystemBackup
    {
        $backup->forceFill([
            'status' => $valid ? 'verified' : 'invalid',
            'verified_at' => now(),
            'verification_message' => $message,
        ])->save();

        return $backup->fresh();
    }
}
