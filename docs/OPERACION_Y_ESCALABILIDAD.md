# Operacion y escalabilidad

Version funcional: `2026.07.23`.

## Procesos en segundo plano

Las importaciones CSV, aprobaciones masivas, PDFs y sincronizaciones de Firebase usan las colas `imports`, `exports`, `integrations` y `default`. Servidor, worker y programador deben estar activos:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\iniciar-sistema.ps1
```

Los procesos muestran porcentaje, estado, error y descarga desde el centro de procesos.

## Programador

Ejecuta `php artisan schedule:run` cada minuto mediante el Programador de tareas de Windows. El sistema programa:

- respaldo diario a las 23:30;
- verificacion semanal de checksum y estructura;
- restauracion aislada mensual;
- limpieza diaria de trabajos fallidos antiguos.

## Reglas y anos futuros

No se fijan anos de operacion en nomina o asistencias. Las semanas se resuelven por fecha y el calendario laboral acepta cualquier ano entre 2020 y 2100. Las excepciones se administran en **Sistema > Reglas laborales**:

- reglas globales, por puesto, coincidencia de puesto, numero o empleado;
- turnos 24x24;
- exclusion de retardos u horas extra;
- horario y dias laborales;
- topes semanales;
- dias laborables o no laborables por ano y alcance.

Una regla de mayor prioridad hereda los campos que deja sin definir.

## Integridad y rendimiento

- Numero de empleado unico en base de datos.
- Una asistencia por empleado y fecha.
- Una nomina por empleado y periodo.
- Indices para fechas, estados, puestos, bancos, semanas y auditoria.
- Paginacion de empleados, asistencias, nominas y auditoria.
- Carga diferida de fotografias y paginas de interfaz.
- Bloqueo de fila e instrucciones de estado explicitas para evitar pagos duplicados.

## Recuperacion

Los respaldos se guardan en `storage/app/private/backups/automatic`. Salud del sistema muestra su estado. Una restauracion de prueba usa una base temporal, valida tablas y conteos criticos y elimina la base temporal al finalizar.

## Diagnostico

**Sistema > Salud del sistema** revisa base de datos, Firebase, cola, almacenamiento, respaldos, operaciones y:

- numeros de empleado duplicados;
- fotografias faltantes;
- asistencias sin empleado;
- nominas sin empleado;
- fallos de integracion abiertos.
