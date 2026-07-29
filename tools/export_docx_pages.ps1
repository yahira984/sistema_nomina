param(
    [Parameter(Mandatory = $true)]
    [string]$DocumentPath,

    [Parameter(Mandatory = $true)]
    [string]$OutputDirectory,

    [int]$Scale = 2,

    [int]$StartPage = 1,

    [int]$EndPage = 0
)

$ErrorActionPreference = 'Stop'

$documentFullPath = (Resolve-Path -LiteralPath $DocumentPath).Path
$outputFullPath = [System.IO.Path]::GetFullPath((Join-Path (Get-Location) $OutputDirectory))

if (-not $outputFullPath.StartsWith([System.IO.Path]::GetFullPath((Get-Location).Path), [System.StringComparison]::OrdinalIgnoreCase)) {
    throw "El directorio de salida debe estar dentro del proyecto."
}

[System.IO.Directory]::CreateDirectory($outputFullPath) | Out-Null
Add-Type -AssemblyName System.Drawing

$word = $null
$document = $null

try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $word.Options.SaveNormalPrompt = $false

    $document = $word.Documents.Open($documentFullPath)
    $pageCount = $document.ComputeStatistics(2)
    Write-Output "Páginas detectadas: $pageCount"

    $lastPage = if ($EndPage -gt 0) { [Math]::Min($EndPage, $pageCount) } else { $pageCount }
    if ($StartPage -lt 1 -or $StartPage -gt $lastPage) {
        throw "El rango de páginas solicitado no es válido."
    }

    for ($page = $StartPage; $page -le $lastPage; $page++) {
        $range = $null
        $metafile = $null
        $bitmap = $null
        $graphics = $null
        $emfPath = Join-Path $outputFullPath ("page-{0:D2}.emf" -f $page)
        $pngPath = Join-Path $outputFullPath ("page-{0:D2}.png" -f $page)

        try {
            $start = $document.GoTo(1, 1, $page).Start
            if ($page -lt $pageCount) {
                $end = $document.GoTo(1, 1, $page + 1).Start - 1
            } else {
                $end = $document.Content.End
            }

            $range = $document.Range($start, $end)
            [System.IO.File]::WriteAllBytes($emfPath, $range.EnhMetaFileBits)

            $metafile = New-Object System.Drawing.Imaging.Metafile($emfPath)
            $width = [Math]::Max(1, $metafile.Width * $Scale)
            $height = [Math]::Max(1, $metafile.Height * $Scale)
            $bitmap = New-Object System.Drawing.Bitmap($width, $height)
            $bitmap.SetResolution(192, 192)
            $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
            $graphics.Clear([System.Drawing.Color]::White)
            $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
            $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
            $graphics.DrawImage($metafile, 0, 0, $width, $height)
            $bitmap.Save($pngPath, [System.Drawing.Imaging.ImageFormat]::Png)

            Write-Output ("Página {0:D2}: {1} x {2}" -f $page, $width, $height)
        }
        finally {
            if ($null -ne $graphics) { $graphics.Dispose() }
            if ($null -ne $bitmap) { $bitmap.Dispose() }
            if ($null -ne $metafile) { $metafile.Dispose() }
            if (Test-Path -LiteralPath $emfPath) { Remove-Item -LiteralPath $emfPath -Force }
            if ($null -ne $range) { [System.Runtime.InteropServices.Marshal]::ReleaseComObject($range) | Out-Null }
        }
    }
}
finally {
    if ($null -ne $document) {
        $document.Close($false)
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($document) | Out-Null
    }
    if ($null -ne $word) {
        $word.Quit()
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
    }
}
