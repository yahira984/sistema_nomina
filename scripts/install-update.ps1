param([Parameter(Mandatory = $true)][string]$ZipPath)

$ErrorActionPreference = 'Stop'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$Php = Join-Path $env:USERPROFILE '.config\herd\bin\php84\php.exe'
$Temp = Join-Path $env:TEMP ("nominas-update-" + [guid]::NewGuid())
$Backup = Join-Path $Root ("storage\app\update-backup-" + (Get-Date -Format 'yyyyMMdd-HHmmss'))

if (-not (Test-Path $ZipPath)) { throw 'No existe el ZIP indicado.' }
if (-not (Test-Path $Php)) { throw 'No se encontro PHP de Herd.' }
if (-not (Test-Path (Join-Path $Root '.env'))) { throw 'No se encontro .env; no continúes con esta actualizacion.' }

New-Item -ItemType Directory -Force -Path $Temp, $Backup | Out-Null
Copy-Item (Join-Path $Root '.env') (Join-Path $Backup '.env') -Force
Copy-Item (Join-Path $Root 'database') (Join-Path $Backup 'database') -Recurse -Force

try {
    Expand-Archive -Path $ZipPath -DestinationPath $Temp -Force
    if (-not (Test-Path (Join-Path $Temp 'release.json'))) { throw 'El ZIP no es una entrega valida del sistema.' }

    & $Php artisan down --retry=30
    Get-ChildItem -Force $Temp | ForEach-Object { Copy-Item $_.FullName $Root -Recurse -Force }
    & $Php artisan migrate --force
    & $Php artisan optimize:clear
    & $Php artisan optimize
    & $Php artisan system:health
}
finally {
    Push-Location $Root
    try { & $Php artisan up } finally { Pop-Location }
    if (Test-Path $Temp) { Remove-Item -LiteralPath $Temp -Recurse -Force }
}

Write-Host "Actualizacion terminada. Respaldo previo: $Backup" -ForegroundColor Green
