$ErrorActionPreference = 'Continue'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$Php = Join-Path $env:USERPROFILE '.config\herd\bin\php84\php.exe'
$Log = Join-Path $Root 'storage\logs\scheduler-supervisor.log'

Set-Location $Root
while ($true) {
    & $Php artisan schedule:run *>> $Log
    Start-Sleep -Seconds 60
}
