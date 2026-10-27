param()
$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$lock = Get-Content -LiteralPath (Join-Path $root 'docs/language-dependency.json') -Raw | ConvertFrom-Json
$output = Join-Path $root 'dist/languages'
New-Item -ItemType Directory -Path $output -Force | Out-Null
$zip = Join-Path $output "polylang.$($lock.version).zip"
if (-not (Test-Path -LiteralPath $zip)) { Invoke-WebRequest -Uri $lock.url -OutFile $zip }
if ((Get-FileHash -LiteralPath $zip -Algorithm SHA256).Hash.ToLowerInvariant() -ne $lock.sha256) { throw 'Контрольная сумма Polylang не совпала. Не устанавливать архив.' }
Write-Host "Проверенный официальный архив: $zip. Установка и настройка — docs/multilingual.md."
