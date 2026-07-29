# Procedimiento de actualización entre computadoras

Este procedimiento no requiere Git en la computadora de destino.

La versión entregada se identifica en el archivo `VERSION` y también aparece al pie del menú lateral. Registra en la bitácora de entrega la versión anterior, la nueva, la fecha, la persona que actualizó y el resultado de la validación.

## Paquete que se entrega

Incluye el proyecto completo, excepto:

- `.env`
- `vendor`
- `node_modules`
- `storage/logs`
- respaldos SQL antiguos

Conserva aparte en la computadora destino:

- su archivo `.env`
- `storage/app/firebase/service-account.json`
- `public/img/empleados`
- cualquier archivo subido por usuarios

## Actualización

1. Cierra el servidor y el worker de cola.
2. Desde el sistema actual genera un respaldo en **Sistema > Base de datos**.
3. Copia la carpeta nueva a una ubicación temporal.
4. Pasa a la carpeta nueva el `.env`, las credenciales de Firebase y las fotos de empleados.
5. Abre PowerShell dentro del proyecto nuevo.
6. Ejecuta:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\actualizar-sistema.ps1
```

7. Inicia servidor y colas:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\iniciar-sistema.ps1
```

8. Abre **Sistema > Salud del sistema** y confirma que base de datos, cola, almacenamiento y respaldo estén correctos.

## Validación obligatoria de entrega

1. Confirma que la versión del menú coincide con `VERSION`.
2. Inicia sesión con un rol administrador y uno operativo.
3. Abre Empleados, Asistencias, Nóminas, Auditoría y Salud del sistema.
4. Revisa una semana anterior sin modificarla.
5. Importa un CSV pequeño y observa el centro de procesos.
6. Genera un PDF de prueba.
7. Verifica que el worker de cola siga ejecutándose.
8. Confirma en Salud del sistema que Firebase no tenga errores pendientes.

## Regla de seguridad

Nunca reemplaces directamente la carpeta que está funcionando. Conserva la versión anterior hasta validar inicio de sesión, empleados, una semana de asistencias, una nómina y Firebase.

## Reversión

1. Detén servidor y cola.
2. Vuelve a iniciar la carpeta anterior.
3. Si una migración cambió datos, restaura el respaldo creado antes de actualizar.

## Operación permanente

- Mantén activos servidor, cola y programador; `tools/iniciar-sistema.ps1` inicia los tres en segundo plano.
- Si la computadora usa servicios permanentes en vez del script, configura el Programador de tareas para ejecutar `php artisan schedule:run` cada minuto.
- Conserva por lo menos una copia de respaldo fuera de la misma computadora.
- No compartas `.env` ni el archivo de credenciales de Firebase dentro del paquete de actualización.
