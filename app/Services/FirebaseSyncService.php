<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Support\HorasExtraEmpleado;
use App\Support\ReglasNominaEmpleado;
use App\Support\SemanaNomina;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Http\HttpClientOptions;
use Throwable;

class FirebaseSyncService
{
    public static function sincronizarEmpleado(Empleado $empleado): void
    {
        try {
            $database = self::database();

            if (!$database) {
                return;
            }

            self::sincronizarEmpleadoConDatabase($database, $empleado);
        } catch (Throwable $e) {
            Log::error('Error sincronizando empleado con Firebase', [
                'empleado_id' => $empleado->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function sincronizarEmpleadoCompleto(Empleado $empleado): void
    {
        try {
            $database = self::database();

            if (!$database) {
                return;
            }

            $empleado = $empleado->fresh() ?: $empleado;
            $basePath = self::pathEmpleado($empleado);

            $database->getReference($basePath . '/asistencias')->remove();
            $database->getReference($basePath . '/nominas')->remove();

            self::sincronizarEmpleadoConDatabase($database, $empleado);

            Asistencia::with('empleado')
                ->where('empleado_id', $empleado->id)
                ->orderBy('fecha')
                ->chunk(100, function ($asistencias) use ($database) {
                    foreach ($asistencias as $asistencia) {
                        self::sincronizarAsistenciaConDatabase($database, $asistencia);
                    }
                });

            Nomina::where('empleado_id', $empleado->id)
                ->where('pagado', true)
                ->orderBy('fecha_inicio')
                ->chunk(50, function ($nominas) use ($database, $empleado) {
                    foreach ($nominas as $nomina) {
                        self::sincronizarNominaPagadaConDatabase($database, $empleado, $nomina, []);
                    }
                });
        } catch (Throwable $e) {
            Log::error('Error sincronizando empleado completo con Firebase', [
                'empleado_id' => $empleado->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function sincronizarAsistencia(Asistencia $asistencia): void
    {
        self::sincronizarAsistencias([$asistencia]);
    }

    public static function sincronizarAsistencias(iterable $asistencias): void
    {
        try {
            $database = self::database();

            if (!$database) {
                return;
            }

            $empleados = [];
            $updates = [];

            foreach ($asistencias as $asistencia) {
                if (!$asistencia instanceof Asistencia) {
                    continue;
                }

                $asistencia->loadMissing('empleado');

                if (!$asistencia->empleado) {
                    continue;
                }

                $updates[self::pathAsistenciaEmpleado($asistencia->empleado, $asistencia)] = self::datosAsistencia($asistencia);
                $empleados[$asistencia->empleado->id] = $asistencia->empleado;

                if (count($updates) >= 1000) {
                    self::aplicarUpdatesDatabase($database, $updates);
                    $updates = [];
                }
            }

            foreach ($empleados as $empleado) {
                foreach (self::updatesEmpleado($empleado->fresh() ?: $empleado) as $path => $payload) {
                    $updates[$path] = $payload;
                }

                if (count($updates) >= 1000) {
                    self::aplicarUpdatesDatabase($database, $updates);
                    $updates = [];
                }
            }

            self::aplicarUpdatesDatabase($database, $updates);
        } catch (Throwable $e) {
            Log::error('Error sincronizando asistencias con Firebase', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function eliminarAsistencia(Asistencia $asistencia): void
    {
        try {
            $database = self::database();

            if (!$database) {
                return;
            }

            $asistencia->loadMissing('empleado');
            $empleado = $asistencia->empleado;

            if (!$empleado) {
                return;
            }

            $database->getReference(self::pathAsistenciaEmpleado($empleado, $asistencia))->remove();
            self::sincronizarEmpleadoConDatabase($database, $empleado->fresh() ?: $empleado);
        } catch (Throwable $e) {
            Log::error('Error eliminando asistencia de Firebase', [
                'asistencia_id' => $asistencia->id,
                'empleado_id' => $asistencia->empleado_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function sincronizarNominaPagada(Empleado $empleado, Nomina $nomina, array $desglose): void
    {
        try {
            $database = self::database();

            if (!$database) {
                return;
            }

            self::sincronizarNominaPagadaConDatabase($database, $empleado, $nomina, $desglose);
            self::sincronizarEmpleadoConDatabase($database, $empleado->fresh() ?: $empleado);
        } catch (Throwable $e) {
            Log::error('Error sincronizando nomina pagada con Firebase', [
                'empleado_id' => $empleado->id,
                'nomina_id' => $nomina->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function eliminarNominaPagada(Empleado $empleado, Nomina $nomina): void
    {
        try {
            $database = self::database();

            if (!$database) {
                return;
            }

            $database->getReference(self::pathNominaEmpleado($empleado, $nomina))->remove();
            $database->getReference(self::pathNominaEmpleadoLegacy($empleado, $nomina))->remove();
            self::sincronizarEmpleadoConDatabase($database, $empleado->fresh() ?: $empleado);
        } catch (Throwable $e) {
            Log::error('Error eliminando nomina de Firebase', [
                'empleado_id' => $empleado->id,
                'nomina_id' => $nomina->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function vincularUsuarioMobile(string $uid, Empleado $empleado): void
    {
        try {
            $database = self::database();
            $uid = self::firebaseKey($uid);

            if (!$database || !$uid) {
                return;
            }

            $database->getReference('usuarios/' . $uid)->set([
                'empleado_id' => $empleado->id,
                'empleado_id_key' => (string) $empleado->id,
                'numero_empleado' => $empleado->numero_empleado ?? $empleado->numero_empleado_baja,
                'nombre_completo' => $empleado->nombre_completo,
                'activo' => (bool) $empleado->estatus,
                'updated_at' => now()->toISOString(),
            ]);

            self::sincronizarEmpleadoConDatabase($database, $empleado);
        } catch (Throwable $e) {
            Log::error('Error vinculando usuario mobile con Firebase', [
                'uid' => $uid,
                'empleado_id' => $empleado->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function obtenerAccesoApp(Empleado $empleado): array
    {
        try {
            $database = self::database();

            if (!$database) {
                return [];
            }

            $value = $database->getReference(self::pathEmpleado($empleado) . '/acceso_app')->getValue();

            return is_array($value) ? $value : [];
        } catch (Throwable $e) {
            Log::error('Error leyendo acceso app de Firebase', [
                'empleado_id' => $empleado->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public static function guardarAccesoApp(Empleado $empleado, string $usuario, string $password): array
    {
        try {
            $auth = self::auth();
            $database = self::database();

            if (!$auth || !$database) {
                return [
                    'ok' => false,
                    'message' => 'Firebase no esta configurado. Revisa FIREBASE_CREDENTIALS y FIREBASE_DATABASE_URL.',
                ];
            }

            $loginUsuario = self::normalizarUsuarioLogin($usuario);
            $emailLogin = self::emailDesdeUsuarioLogin($loginUsuario);

            try {
                $user = $auth->getUserByEmail($emailLogin);
                $auth->changeUserPassword($user->uid, $password);
                $user = $auth->updateUser($user->uid, [
                    'displayName' => $empleado->nombre_completo,
                    'disabled' => false,
                ]);
            } catch (UserNotFound) {
                $user = $auth->createUserWithEmailAndPassword($emailLogin, $password);
                $user = $auth->updateUser($user->uid, [
                    'displayName' => $empleado->nombre_completo,
                    'disabled' => false,
                ]);
            }

            self::guardarVinculoAccesoApp($database, $user->uid, $empleado, $loginUsuario, $emailLogin, true);
            self::sincronizarEmpleadoConDatabase($database, $empleado);

            return [
                'ok' => true,
                'uid' => $user->uid,
                'usuario' => $loginUsuario,
                'email' => $emailLogin,
            ];
        } catch (Throwable $e) {
            Log::error('Error guardando acceso app en Firebase', [
                'empleado_id' => $empleado->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => self::mensajeFirebase($e),
            ];
        }
    }

    public static function desactivarAccesoApp(Empleado $empleado): array
    {
        try {
            $auth = self::auth();
            $database = self::database();

            if (!$auth || !$database) {
                return [
                    'ok' => false,
                    'message' => 'Firebase no esta configurado.',
                ];
            }

            $acceso = self::obtenerAccesoApp($empleado);
            $uid = self::firebaseKey((string) ($acceso['uid'] ?? ''));

            if (!$uid) {
                return [
                    'ok' => false,
                    'message' => 'Este empleado no tiene acceso app vinculado.',
                ];
            }

            $auth->disableUser($uid);

            $database->getReference('usuarios/' . $uid)->update([
                'activo' => false,
                'updated_at' => now()->toISOString(),
            ]);

            $database->getReference(self::pathEmpleado($empleado) . '/acceso_app')->update([
                'activo' => false,
                'updated_at' => now()->toISOString(),
            ]);

            return ['ok' => true];
        } catch (Throwable $e) {
            Log::error('Error desactivando acceso app en Firebase', [
                'empleado_id' => $empleado->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => self::mensajeFirebase($e),
            ];
        }
    }

    private static function database()
    {
        static $database = null;
        static $resuelto = false;

        if ($resuelto) {
            return $database;
        }

        $resuelto = true;
        $databaseUrl = config('services.firebase.database_url');

        if (!$databaseUrl) {
            Log::warning('Firebase no configurado: faltan FIREBASE_CREDENTIALS o FIREBASE_DATABASE_URL.');
            return null;
        }

        $credentialsPath = self::credentialsPath();

        if (!$credentialsPath) {
            return null;
        }

        $database = (new Factory)
            ->withServiceAccount($credentialsPath)
            ->withDatabaseUri($databaseUrl)
            ->withHttpClientOptions(
                HttpClientOptions::default()
                    ->withConnectTimeout((float) config('services.firebase.connect_timeout', 2))
                    ->withTimeout((float) config('services.firebase.timeout', 8))
            )
            ->createDatabase();

        return $database;
    }

    private static function auth()
    {
        static $auth = null;
        static $resuelto = false;

        if ($resuelto) {
            return $auth;
        }

        $resuelto = true;
        $credentialsPath = self::credentialsPath();

        if (!$credentialsPath) {
            return null;
        }

        $auth = (new Factory)
            ->withServiceAccount($credentialsPath)
            ->createAuth();

        return $auth;
    }

    private static function credentialsPath(): ?string
    {
        $credentials = config('services.firebase.credentials');

        if (!$credentials) {
            Log::warning('Firebase no configurado: falta FIREBASE_CREDENTIALS.');
            return null;
        }

        $credentialsPath = self::absolutePath($credentials)
            ? $credentials
            : storage_path($credentials);

        if (!is_file($credentialsPath)) {
            Log::warning('Firebase no configurado: no existe el archivo de credenciales.', [
                'path' => $credentialsPath,
            ]);
            return null;
        }

        return $credentialsPath;
    }

    private static function guardarVinculoAccesoApp($database, string $uid, Empleado $empleado, string $loginUsuario, string $emailLogin, bool $activo): void
    {
        $usuarioPayload = [
            'empleado_id' => $empleado->id,
            'empleado_id_key' => (string) $empleado->id,
            'numero_empleado' => $empleado->numero_empleado ?? $empleado->numero_empleado_baja,
            'nombre_completo' => $empleado->nombre_completo,
            'login_usuario' => $loginUsuario,
            'email_login' => $emailLogin,
            'activo' => $activo,
            'updated_at' => now()->toISOString(),
        ];

        $database->getReference('usuarios/' . $uid)->set($usuarioPayload);
        $database->getReference(self::pathEmpleado($empleado) . '/acceso_app')->set(array_merge($usuarioPayload, [
            'uid' => $uid,
        ]));
    }

    private static function sincronizarEmpleadoConDatabase($database, Empleado $empleado): void
    {
        self::aplicarUpdatesDatabase($database, self::updatesEmpleado($empleado->fresh() ?: $empleado));
    }

    private static function sincronizarAsistenciaConDatabase($database, Asistencia $asistencia): void
    {
        $asistencia->loadMissing('empleado');

        if (!$asistencia->empleado) {
            return;
        }

        $database->getReference(self::pathAsistenciaEmpleado($asistencia->empleado, $asistencia))
            ->set(self::datosAsistencia($asistencia));
    }

    private static function aplicarUpdatesDatabase($database, array $updates): void
    {
        if ($updates === []) {
            return;
        }

        $database->getReference()->update($updates);
    }

    private static function updatesEmpleado(Empleado $empleado): array
    {
        $basePath = self::pathEmpleado($empleado);

        return [
            $basePath . '/perfil' => self::datosPerfilEmpleado($empleado),
            $basePath . '/resumen' => self::datosResumenEmpleado($empleado),
            $basePath . '/meta/updated_at' => now()->toISOString(),
        ];
    }

    private static function sincronizarNominaPagadaConDatabase($database, Empleado $empleado, Nomina $nomina, array $desglose): void
    {
        $database->getReference(self::pathNominaEmpleado($empleado, $nomina))
            ->set(self::datosNominaPagada($empleado, $nomina, $desglose));

        $legacyPath = self::pathNominaEmpleadoLegacy($empleado, $nomina);

        if ($legacyPath !== self::pathNominaEmpleado($empleado, $nomina)) {
            $database->getReference($legacyPath)->remove();
        }
    }

    private static function datosPerfilEmpleado(Empleado $empleado): array
    {
        return [
            'id' => $empleado->id,
            'numero_empleado' => $empleado->numero_empleado ?? $empleado->numero_empleado_baja,
            'numero_empleado_activo' => $empleado->numero_empleado,
            'numero_empleado_baja' => $empleado->numero_empleado_baja,
            'nombre_completo' => $empleado->nombre_completo,
            'puesto' => $empleado->puesto,
            'estatus' => (bool) $empleado->estatus,
            'es_estudiante' => (bool) ($empleado->es_estudiante ?? false),
            'fecha_ingreso' => self::fecha($empleado->fecha_ingreso),
            'fecha_baja' => self::fecha($empleado->fecha_baja),
            'dias_laborados' => (int) ($empleado->dias_laborados ?? 0),
            'antiguedad_anios' => (int) ($empleado->antiguedad_anios ?? 0),
            'forma_pago' => $empleado->forma_pago,
            'banco' => $empleado->banco,
            'updated_at' => now()->toISOString(),
        ];
    }

    private static function datosResumenEmpleado(Empleado $empleado): array
    {
        return [
            'vacaciones' => [
                'dias_totales' => self::numero($empleado->dias_vacaciones_totales ?? 0),
                'dias_tomados' => self::numero($empleado->dias_vacaciones_tomados ?? 0),
                'dias_restantes' => self::numero($empleado->dias_vacaciones_restantes ?? 0),
                'ajuste' => self::numero($empleado->ajuste_vacaciones ?? 0),
            ],
            'prestamo' => [
                'saldo_actual' => self::numero($empleado->saldo_prestamo ?? 0),
                'descuento_semanal' => self::numero($empleado->cuota_prestamo ?? 0),
                'tiene_prestamo' => ((float) ($empleado->saldo_prestamo ?? 0)) > 0,
            ],
            'faltas' => [
                'total' => (int) ($empleado->dias_faltas_totales ?? 0),
                'fechas' => collect($empleado->fechas_faltas ?? [])->take(50)->values()->all(),
            ],
            'retardos' => self::resumenRetardosEmpleado($empleado),
            'ultima_nomina_pagada' => self::ultimaNominaPagadaEmpleado($empleado),
            'updated_at' => now()->toISOString(),
        ];
    }

    private static function datosAsistencia(Asistencia $asistencia): array
    {
        $empleado = $asistencia->empleado;
        $horasExtra = $asistencia->tipo_asistencia === 'Normal'
            && $empleado
            && !(bool) ($empleado->es_estudiante ?? false)
            ? HorasExtraEmpleado::calcular(
                $empleado,
                $asistencia->fecha,
                $asistencia->hora_entrada,
                $asistencia->hora_salida
            )
            : 0;

        return [
            'id' => $asistencia->id,
            'fecha' => self::fecha($asistencia->fecha),
            'tipo_asistencia' => $asistencia->tipo_asistencia,
            'hora_entrada' => self::hora($asistencia->hora_entrada),
            'hora_salida' => self::hora($asistencia->hora_salida),
            'minutos_tarde' => (int) ($asistencia->minutos_tarde ?? 0),
            'horas_trabajadas' => self::numero($asistencia->horas_trabajadas ?? 0),
            'horas_extra' => self::numero($horasExtra),
            'es_falta' => $asistencia->tipo_asistencia === 'Falta',
            'updated_at' => now()->toISOString(),
        ];
    }

    private static function datosNominaPagada(Empleado $empleado, Nomina $nomina, array $desglose): array
    {
        $fechaInicio = self::fecha($nomina->fecha_inicio);
        $fechaFin = self::fecha($nomina->fecha_fin);

        return [
            'id' => $nomina->id,
            'empleado_id' => $empleado->id,
            'numero_empleado' => $empleado->numero_empleado ?? $empleado->numero_empleado_baja,
            'empleado_nombre' => $empleado->nombre_completo,
            'semana' => (int) $nomina->numero_semana,
            'anio' => self::anioNomina($nomina),
            'periodo' => $fechaInicio . ' al ' . $fechaFin,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'fecha_pago' => now()->toDateTimeString(),
            'horas_normales' => self::numero($desglose['horas_normales'] ?? $nomina->horas_normales ?? 0),
            'horas_extra' => self::numero($desglose['horas_extra_pagadas'] ?? $nomina->horas_extra_pagadas ?? $nomina->horas_extra ?? 0),
            'total_percepciones' => self::numero($desglose['total_percepciones'] ?? $nomina->total_percepciones ?? 0),
            'total_deducciones' => self::numero($desglose['total_deducciones'] ?? $nomina->total_deducciones ?? 0),
            'pago_neto' => self::numero($desglose['pago_neto'] ?? $nomina->pago_neto ?? 0),
            'percepciones' => [
                'sueldo_base' => self::numero($desglose['pago_normal'] ?? 0),
                'horas_extra' => self::numero($desglose['pago_extra'] ?? 0),
                'incapacidad' => self::numero($desglose['pago_incapacidad'] ?? 0),
                'vacaciones' => self::numero($desglose['pago_vacaciones'] ?? 0),
                'prestamo_entregado' => self::numero($desglose['prestamo_otorgado'] ?? $nomina->prestamo_otorgado ?? 0),
            ],
            'deducciones' => [
                'faltas' => self::numero($desglose['descuento_faltas'] ?? 0),
                'retardos' => self::numero($desglose['descuento_retardos'] ?? 0),
                'prestamo' => self::numero($desglose['prestamo_descuento'] ?? $nomina->prestamo_descuento ?? 0),
                'manual' => self::numero($desglose['deduccion_manual'] ?? $nomina->deduccion_manual ?? 0),
                'imss' => self::numero($desglose['descuento_imss'] ?? 0),
                'isr' => self::numero($desglose['descuento_isr'] ?? 0),
                'infonavit' => self::numero($desglose['descuento_infonavit'] ?? 0),
            ],
            'incidencias' => [
                'faltas_detectadas' => self::numero($desglose['dias_falta'] ?? 0),
                'faltas_descontadas' => self::numero($desglose['dias_falta_descontables'] ?? 0),
                'faltas_pagadas' => self::numero($desglose['dias_falta_pagados'] ?? $nomina->faltas_pagadas ?? 0),
                'vacaciones_pagadas' => self::numero($desglose['dias_vacaciones_pagadas'] ?? $nomina->dias_vacaciones_pagadas ?? 0),
                'incapacidad_pagada' => self::numero($desglose['dias_incapacidad'] ?? 0),
                'minutos_tarde' => (int) ($desglose['minutos_tarde_acumulados'] ?? 0),
                'minutos_tarde_descontados' => (int) ($desglose['minutos_tarde_descontables'] ?? 0),
            ],
            'pagado' => true,
            'updated_at' => now()->toISOString(),
        ];
    }

    private static function resumenRetardosEmpleado(Empleado $empleado): array
    {
        if ((bool) ($empleado->es_estudiante ?? false) || ReglasNominaEmpleado::sinRetardos($empleado)) {
            return self::resumenRetardosVacio();
        }

        [$inicioSemana, $finSemana] = SemanaNomina::desdeCorte(SemanaNomina::corteActual()->format('Y-m-d'));
        $hoy = Carbon::now();

        return [
            'semana' => self::retardosEntre($empleado, $inicioSemana, $finSemana),
            'mes' => self::retardosEntre($empleado, $hoy->copy()->startOfMonth(), $hoy->copy()->endOfMonth()),
            'anio' => self::retardosEntre($empleado, $hoy->copy()->startOfYear(), $hoy->copy()->endOfYear()),
        ];
    }

    private static function resumenRetardosVacio(): array
    {
        $vacio = [
            'minutos' => 0,
            'dias' => 0,
            'mayor_retardo' => 0,
        ];

        return [
            'semana' => $vacio,
            'mes' => $vacio,
            'anio' => $vacio,
        ];
    }

    private static function retardosEntre(Empleado $empleado, Carbon $inicio, Carbon $fin): array
    {
        $asistencias = Asistencia::where('empleado_id', $empleado->id)
            ->where('tipo_asistencia', 'Normal')
            ->whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->where('minutos_tarde', '>', 0)
            ->get()
            ->filter(fn (Asistencia $asistencia) => self::retardoDescontable($asistencia));

        return [
            'minutos' => (int) $asistencias->sum('minutos_tarde'),
            'dias' => $asistencias->count(),
            'mayor_retardo' => (int) $asistencias->max('minutos_tarde'),
        ];
    }

    private static function retardoDescontable(Asistencia $asistencia): bool
    {
        if (!$asistencia->hora_entrada || !$asistencia->hora_salida) {
            return false;
        }

        $fecha = Carbon::parse($asistencia->fecha);

        if ($fecha->isWeekend()) {
            return false;
        }

        $fechaBase = $fecha->format('Y-m-d');
        $entrada = Carbon::parse($fechaBase . ' ' . $asistencia->hora_entrada);
        $salida = Carbon::parse($fechaBase . ' ' . $asistencia->hora_salida);
        $horaOficial = Carbon::parse($fechaBase . ' 08:00:00');
        $limiteMarcaSalida = Carbon::parse($fechaBase . ' 16:00:00');

        return $salida->greaterThan($entrada)
            && $entrada->greaterThan($horaOficial)
            && $entrada->lessThan($limiteMarcaSalida);
    }

    private static function ultimaNominaPagadaEmpleado(Empleado $empleado): ?array
    {
        $nomina = Nomina::where('empleado_id', $empleado->id)
            ->where('pagado', true)
            ->orderBy('fecha_fin', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (!$nomina) {
            return null;
        }

        return [
            'id' => $nomina->id,
            'semana' => (int) $nomina->numero_semana,
            'anio' => self::anioNomina($nomina),
            'fecha_inicio' => self::fecha($nomina->fecha_inicio),
            'fecha_fin' => self::fecha($nomina->fecha_fin),
            'pago_neto' => self::numero($nomina->pago_neto ?? 0),
        ];
    }

    private static function pathNominaEmpleado(Empleado $empleado, Nomina $nomina): string
    {
        return self::pathEmpleado($empleado) . '/nominas/' . self::keyNomina($nomina);
    }

    private static function pathNominaEmpleadoLegacy(Empleado $empleado, Nomina $nomina): string
    {
        return self::pathEmpleado($empleado) . '/nominas/semana_' . $nomina->numero_semana;
    }

    private static function pathAsistenciaEmpleado(Empleado $empleado, Asistencia $asistencia): string
    {
        return self::pathEmpleado($empleado) . '/asistencias/' . self::fecha($asistencia->fecha);
    }

    private static function pathEmpleado(Empleado $empleado): string
    {
        return 'empleados/' . $empleado->id;
    }

    private static function keyNomina(Nomina $nomina): string
    {
        return self::anioNomina($nomina) . '_semana_' . $nomina->numero_semana;
    }

    private static function anioNomina(Nomina $nomina): int
    {
        return (int) Carbon::parse($nomina->fecha_fin ?? $nomina->fecha_inicio ?? now())->year;
    }

    private static function fecha($fecha): ?string
    {
        if (!$fecha) {
            return null;
        }

        try {
            return Carbon::parse($fecha)->format('Y-m-d');
        } catch (Throwable $e) {
            return (string) $fecha;
        }
    }

    private static function hora($hora): ?string
    {
        if (!$hora) {
            return null;
        }

        return substr((string) $hora, 0, 5);
    }

    private static function numero($valor): float
    {
        return round((float) ($valor ?? 0), 2);
    }

    private static function firebaseKey(string $key): ?string
    {
        if ($key === '' || preg_match('/[.#$\[\]\/]/', $key)) {
            Log::warning('Firebase key invalida para usuario mobile.', ['key' => $key]);
            return null;
        }

        return $key;
    }

    private static function normalizarUsuarioLogin(string $usuario): string
    {
        $usuario = strtolower(trim($usuario));

        return preg_replace('/[^a-z0-9._@-]/', '', $usuario) ?: '';
    }

    private static function emailDesdeUsuarioLogin(string $usuario): string
    {
        if (str_contains($usuario, '@')) {
            return $usuario;
        }

        $domain = strtolower(trim((string) config('services.firebase.auth_login_domain', 'mi-lugarth.app')));
        $domain = preg_replace('/[^a-z0-9.-]/', '', $domain) ?: 'mi-lugarth.app';

        return $usuario . '@' . $domain;
    }

    private static function mensajeFirebase(Throwable $e): string
    {
        $mensaje = $e->getMessage();

        if (str_contains($mensaje, 'CONFIGURATION_NOT_FOUND')) {
            return 'Firebase Authentication no esta habilitado. En Firebase Console entra a Authentication > Comenzar > Sign-in method y activa Email/Password.';
        }

        if (str_contains($mensaje, 'EMAIL_EXISTS')) {
            return 'Ese usuario/correo ya existe en Firebase Auth. Usa otro usuario o cambia la contrasena del acceso existente.';
        }

        if (str_contains($mensaje, 'INVALID_PASSWORD') || str_contains($mensaje, 'WEAK_PASSWORD')) {
            return 'La contrasena no cumple los requisitos de Firebase. Usa minimo 6 caracteres.';
        }

        if (str_contains($mensaje, 'INSUFFICIENT_PERMISSION') || str_contains($mensaje, 'PERMISSION_DENIED')) {
            return 'La cuenta de servicio de Firebase no tiene permisos suficientes para administrar usuarios.';
        }

        return $mensaje;
    }

    private static function absolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Z]:[\\\\\\/]/i', $path) === 1;
    }
}
