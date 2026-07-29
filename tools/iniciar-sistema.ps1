param(
    [string]$Php = "$env:USERPROFILE\.config\herd\bin\php84\php.exe",
    [string]$HostAddress = "0.0.0.0",
    [int]$Port = 8000
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
$Logs = Join-Path $Root 'storage\logs'

if (-not (Test-Path -LiteralPath $Php)) {
    throw "No se encontró PHP en $Php. Indica la ruta con -Php."
}

New-Item -ItemType Directory -Force -Path $Logs | Out-Null

$queue = Start-Process -FilePath $Php `
    -ArgumentList @('artisan', 'queue:work', '--queue=imports,exports,integrations,default', '--sleep=2', '--tries=3', '--timeout=900') `
    -WorkingDirectory $Root `
    -WindowStyle Hidden `
    -RedirectStandardOutput (Join-Path $Logs 'queue-worker.log') `
    -RedirectStandardError (Join-Path $Logs 'queue-worker-error.log') `
    -PassThru

$server = Start-Process -FilePath $Php `
    -ArgumentList @('artisan', 'serve', "--host=$HostAddress", "--port=$Port") `
    -WorkingDirectory $Root `
    -WindowStyle Hidden `
    -RedirectStandardOutput (Join-Path $Logs 'server.log') `
    -RedirectStandardError (Join-Path $Logs 'server-error.log') `
    -PassThru

$scheduler = Start-Process -FilePath $Php `
    -ArgumentList @('artisan', 'schedule:work') `
    -WorkingDirectory $Root `
    -WindowStyle Hidden `
    -RedirectStandardOutput (Join-Path $Logs 'scheduler.log') `
    -RedirectStandardError (Join-Path $Logs 'scheduler-error.log') `
    -PassThru

Write-Host "Sistema iniciado en http://localhost:$Port"
Write-Host "Servidor PID: $($server.Id)"
Write-Host "Cola PID: $($queue.Id)"
Write-Host "Programador PID: $($scheduler.Id)"
Write-Host "Los equipos de la red deben abrir http://IP-DE-ESTA-PC:$Port"
