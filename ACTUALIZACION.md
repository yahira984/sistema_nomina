# Actualizacion entre computadoras

1. En la computadora principal, ejecuta `powershell -ExecutionPolicy Bypass -File scripts/create-release.ps1 -Version 2026.08.01`.
2. Lleva el ZIP creado en `storage/app/releases` a la otra computadora.
3. Conserva intactos `.env`, la base de datos y `public/img/empleados` de la computadora destino.
4. En la computadora destino ejecuta `powershell -ExecutionPolicy Bypass -File scripts/install-update.ps1 -ZipPath C:\ruta\sistema-nominas-version.zip`.
5. Inicia de forma permanente el worker `php artisan queue:work --queue=imports,exports,integrations,default --sleep=2 --tries=3 --timeout=1200` y el programador `php artisan schedule:work`.
6. Abre **Salud del sistema** y confirma base de datos, almacenamiento, Firebase, worker y respaldo en verde.

El instalador pone el sistema en mantenimiento, conserva un respaldo previo, aplica migraciones, limpia cache y ejecuta el diagnostico final. No requiere Git.
