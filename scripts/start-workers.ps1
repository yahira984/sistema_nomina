param(
    [string]$ProjectPath = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'
$artisan = Join-Path $ProjectPath 'artisan'

if (-not (Test-Path -LiteralPath $artisan)) {
    throw "No se encontro artisan en $ProjectPath"
}

$phpCommand = Get-Command php -ErrorAction SilentlyContinue
$herdPhp = Join-Path $env:USERPROFILE '.config\herd\bin\php84\php.exe'
$php = if ($phpCommand) { $phpCommand.Source } elseif (Test-Path -LiteralPath $herdPhp) { $herdPhp } else { throw 'No se encontro PHP en PATH ni en Herd.' }
$existing = Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" |
    Where-Object { $_.CommandLine -like "*artisan queue:work*" -and $_.CommandLine -like "*$ProjectPath*" }

if ($existing) {
    Write-Output "El worker ya esta activo (PID $($existing.ProcessId -join ', '))."
    exit 0
}

$arguments = @(
    $artisan,
    'queue:work',
    'database',
    '--queue=integrations,imports,exports,default',
    '--sleep=1',
    '--tries=5',
    '--timeout=1200',
    '--max-time=86400'
)

$logs = Join-Path $ProjectPath 'storage\logs'
New-Item -ItemType Directory -Path $logs -Force | Out-Null
$process = Start-Process -FilePath $php -ArgumentList $arguments -WorkingDirectory $ProjectPath -WindowStyle Hidden -PassThru `
    -RedirectStandardOutput (Join-Path $logs 'queue-worker.log') `
    -RedirectStandardError (Join-Path $logs 'queue-worker-error.log')
Write-Output "Worker iniciado correctamente (PID $($process.Id))."
