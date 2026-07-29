<?php

namespace App\Services;

use App\Models\SystemBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DatabaseBackupService
{
    public function createAutomatic(): SystemBackup
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('Los respaldos automáticos están preparados para MySQL.');
        }

        $sql = $this->generateSql();
        $database = preg_replace('/[^A-Za-z0-9_-]/', '_', DB::getDatabaseName() ?: 'database');
        $fileName = 'backup_' . $database . '_' . now()->format('Ymd_His') . '.sql';
        $path = 'backups/automatic/' . $fileName;

        Storage::disk('local')->put($path, $sql);

        $attributes = [
            'disk' => 'local',
            'path' => $path,
            'size_bytes' => strlen($sql),
            'checksum' => hash('sha256', $sql),
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

        $sql = Storage::disk($backup->disk)->get($backup->path);
        $checksumValid = hash_equals((string) $backup->checksum, hash('sha256', $sql));
        $hasHeader = str_contains($sql, '-- Respaldo generado por Sistema de Nominas');
        $requiredTables = collect(['empleados', 'asistencias', 'nominas', 'users'])
            ->every(fn (string $table) => str_contains($sql, "CREATE TABLE `{$table}`"));
        $hasClosing = str_contains($sql, 'SET FOREIGN_KEY_CHECKS=1;');
        $valid = $checksumValid && $hasHeader && $requiredTables && $hasClosing;

        return $this->markVerification(
            $backup,
            $valid,
            $valid
                ? 'Integridad, estructura crítica y cierre SQL verificados.'
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

            foreach ($this->splitSql(Storage::disk($backup->disk)->get($backup->path)) as $statement) {
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
