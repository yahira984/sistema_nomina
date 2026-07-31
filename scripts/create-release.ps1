param(
    [string]$Version = (Get-Date -Format 'yyyy.MM.dd.HHmm'),
    [switch]$SkipTests
)

$ErrorActionPreference = 'Stop'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$Php = Join-Path $env:USERPROFILE '.config\herd\bin\php84\php.exe'
$ReleaseRoot = Join-Path $Root 'storage\app\releases'
$Stage = Join-Path $env:TEMP "sistema-nominas-stage-$Version"
$Zip = Join-Path $ReleaseRoot "sistema-nominas-$Version.zip"

if (-not (Test-Path $Php)) { throw 'No se encontro PHP 8.4 de Herd.' }
if (-not (Test-Path (Join-Path $Root '.env'))) { throw 'Falta el archivo .env.' }
if (-not (Test-Path (Join-Path $Root 'vendor\autoload.php'))) { throw 'Ejecuta composer install antes de crear la entrega.' }

Push-Location $Root
try {
    if (-not $SkipTests) { & $Php artisan test --compact }
    npm run build
    & $Php artisan about --only=environment

    New-Item -ItemType Directory -Force -Path $ReleaseRoot | Out-Null
    if (Test-Path $Stage) { Remove-Item -LiteralPath $Stage -Recurse -Force }
    New-Item -ItemType Directory -Force -Path $Stage | Out-Null

    $excluded = @('.git', 'node_modules', '.env', 'storage')
    Get-ChildItem -Force $Root | Where-Object {
        $name = $_.Name
        -not ($excluded | Where-Object { $_ -eq $name })
    } | ForEach-Object { Copy-Item $_.FullName $Stage -Recurse -Force }

    Remove-Item -LiteralPath (Join-Path $Stage 'public\img\empleados') -Recurse -Force -ErrorAction SilentlyContinue
    @('app', 'framework\cache', 'framework\sessions', 'framework\views', 'logs') | ForEach-Object {
        New-Item -ItemType Directory -Force -Path (Join-Path $Stage "storage\$_") | Out-Null
    }

    @{
        version = $Version
        created_at = (Get-Date).ToString('o')
        php_required = '8.3+'
        migrate = $true
        build_included = $true
    } | ConvertTo-Json | Set-Content (Join-Path $Stage 'release.json') -Encoding UTF8

    Get-ChildItem $Stage -Recurse -File | ForEach-Object {
        $relative = $_.FullName.Substring($Stage.Length + 1)
        "$((Get-FileHash $_.FullName -Algorithm SHA256).Hash)  $relative"
    } | Set-Content (Join-Path $Stage 'checksums.sha256') -Encoding UTF8

    if (Test-Path $Zip) { Remove-Item -LiteralPath $Zip -Force }
    Compress-Archive -Path (Join-Path $Stage '*') -DestinationPath $Zip -CompressionLevel Optimal
    Write-Host "Entrega lista: $Zip" -ForegroundColor Green
}
finally {
    Pop-Location
    if (Test-Path $Stage) { Remove-Item -LiteralPath $Stage -Recurse -Force }
}
