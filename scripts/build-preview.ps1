param()
$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$output = Join-Path $root 'dist/preview'
New-Item -ItemType Directory -Path $output -Force | Out-Null
Add-Type -AssemblyName System.IO.Compression.FileSystem
$components = @{
    'vniipo-main' = 'wp-content/themes/vniipo-main'
    'vniipo-core' = 'wp-content/plugins/vniipo-core'
    'vniipo-preview' = 'wp-content/plugins/vniipo-preview'
}
$checksums = @()
foreach ($name in ($components.Keys | Sort-Object)) {
    $source = Join-Path $root $components[$name]
    $archive = Join-Path $output "$name.zip"
    if (Test-Path -LiteralPath $archive) { Remove-Item -LiteralPath $archive }
    [IO.Compression.ZipFile]::CreateFromDirectory($source, $archive, [IO.Compression.CompressionLevel]::Optimal, $true)
    $hash = (Get-FileHash -LiteralPath $archive -Algorithm SHA256).Hash.ToLowerInvariant()
    $checksums += "$hash  $name.zip"
}
[IO.File]::WriteAllLines((Join-Path $output 'SHA256SUMS.txt'), $checksums)
Copy-Item -LiteralPath (Join-Path $root 'docs/hosting-preview.md') -Destination (Join-Path $output 'INSTALL.md') -Force
Write-Host 'Пакет для админки WordPress создан в dist/preview. Публичную тему переключать не требуется.'
