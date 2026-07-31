$ErrorActionPreference = 'Continue'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$Php = Join-Path $env:USERPROFILE '.config\herd\bin\php84\php.exe'
$Log = Join-Path $Root 'storage\logs\queue-supervisor.log'

Set-Location $Root
while ($true) {
    "$(Get-Date -Format s) iniciando worker" | Add-Content $Log
    & $Php artisan queue:work database --queue=imports,exports,integrations,default --sleep=2 --tries=3 --timeout=1200 --max-time=3600 *>> $Log
    "$(Get-Date -Format s) worker detenido; reinicio en 5 segundos" | Add-Content $Log
    Start-Sleep -Seconds 5
}
