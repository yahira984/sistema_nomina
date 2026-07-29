param(
    [string]$Php = "$env:USERPROFILE\.config\herd\bin\php84\php.exe",
    [switch]$SkipDependencies
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $Root
$VersionFile = Join-Path $Root 'VERSION'
$Version = if (Test-Path -LiteralPath $VersionFile) { (Get-Content -LiteralPath $VersionFile -Raw).Trim() } else { 'sin-version' }

if (-not (Test-Path -LiteralPath $Php)) {
    throw "No se encontró PHP en $Php. Indica la ruta con -Php."
}

Write-Host "Actualizando Sistema de Nominas a la version $Version"
Write-Host "[1/7] Verificando entorno..."
& $Php artisan about

Write-Host "[2/7] Creando respaldo previo..."
try {
    & $Php artisan system:backup
} catch {
    Write-Warning "No se pudo crear el respaldo automático previo. Revisa la conexión antes de continuar."
    throw
}

if (-not $SkipDependencies) {
    Write-Host "[3/7] Instalando dependencias PHP..."
    composer install --no-interaction --prefer-dist --optimize-autoloader

    Write-Host "[4/7] Instalando dependencias web..."
    npm.cmd ci
} else {
    Write-Host "[3/7] Dependencias PHP omitidas."
    Write-Host "[4/7] Dependencias web omitidas."
}

Write-Host "[5/7] Compilando interfaz..."
npm.cmd run build

Write-Host "[6/7] Aplicando migraciones..."
& $Php artisan migrate --force
Write-Host "Registrando respaldo posterior a las migraciones..."
& $Php artisan system:backup

Write-Host "[7/7] Limpiando y regenerando cachés..."
& $Php artisan optimize:clear
& $Php artisan config:cache
& $Php artisan view:cache
& $Php artisan system:health

Write-Host ""
Write-Host "Actualización completada. Reinicia el servidor y el worker de cola."
