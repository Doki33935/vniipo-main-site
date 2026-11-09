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
$manifestComponents = @()
foreach ($name in ($components.Keys | Sort-Object)) {
    $source = Join-Path $root $components[$name]
    $archive = Join-Path $output "$name.zip"
    if (Test-Path -LiteralPath $archive) { Remove-Item -LiteralPath $archive }
    [IO.Compression.ZipFile]::CreateFromDirectory($source, $archive, [IO.Compression.CompressionLevel]::Optimal, $true)
    $hash = (Get-FileHash -LiteralPath $archive -Algorithm SHA256).Hash.ToLowerInvariant()
    $checksums += "$hash  $name.zip"
    $manifestComponents += [ordered]@{
        archive = "$name.zip"
        directory = $name
        kind = $(if ($name -eq 'vniipo-main') { 'theme' } else { 'plugin' })
        activate = ($name -eq 'vniipo-preview')
        bytes = (Get-Item -LiteralPath $archive).Length
        sha256 = $hash
    }
}
[IO.File]::WriteAllLines((Join-Path $output 'SHA256SUMS.txt'), $checksums)
Copy-Item -LiteralPath (Join-Path $root 'docs/hosting-preview.md') -Destination (Join-Path $output 'INSTALL.md') -Force
Copy-Item -LiteralPath (Join-Path $root 'docs/hosting-preview-quickstart.html') -Destination (Join-Path $output 'START-HERE.html') -Force
Copy-Item -LiteralPath (Join-Path $root 'docs/hosting-preview-start.txt') -Destination (Join-Path $output 'READ-ME-FIRST.txt') -Force
$version = (Get-Content -LiteralPath (Join-Path $root 'package.json') -Raw | ConvertFrom-Json).version
$manifest = [ordered]@{
    version = $version
    mode = 'administrator-preview-only'
    minimum = [ordered]@{ wordpress = '6.9'; preview_php = '8.3' }
    preserve_active_conference_theme = $true
    import_content = $false
    components = $manifestComponents
}
[IO.File]::WriteAllText((Join-Path $output 'MANIFEST.json'), ($manifest | ConvertTo-Json -Depth 6) + "`n", [Text.UTF8Encoding]::new($false))
$delivery = Join-Path $root 'dist/hosting'
New-Item -ItemType Directory -Path $delivery -Force | Out-Null
$bundle = Join-Path $delivery "vniipo-hosting-preview-$version.zip"
$temporary = Join-Path $delivery "vniipo-hosting-preview-$version.tmp.zip"
if (Test-Path -LiteralPath $temporary) { Remove-Item -LiteralPath $temporary }
$allowedFiles = @('vniipo-main.zip', 'vniipo-core.zip', 'vniipo-preview.zip', 'SHA256SUMS.txt', 'INSTALL.md', 'START-HERE.html', 'READ-ME-FIRST.txt', 'MANIFEST.json')
$bundleZip = [IO.Compression.ZipFile]::Open($temporary, [IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($file in $allowedFiles) { [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($bundleZip, (Join-Path $output $file), $file, [IO.Compression.CompressionLevel]::Optimal) | Out-Null }
} finally { $bundleZip.Dispose() }
Move-Item -LiteralPath $temporary -Destination $bundle -Force
$bundleHash = (Get-FileHash -LiteralPath $bundle -Algorithm SHA256).Hash.ToLowerInvariant()
[IO.File]::WriteAllText((Join-Path $delivery "vniipo-hosting-preview-$version.sha256"), "$bundleHash  vniipo-hosting-preview-$version.zip`n", [Text.UTF8Encoding]::new($false))
Write-Host "Комплект для хостинга: $bundle. Сначала распаковать и открыть START-HERE.html; активировать только vniipo-preview."
