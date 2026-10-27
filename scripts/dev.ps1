param([switch]$SkipStart)
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
if (-not (Test-Path -LiteralPath '.env')) {
    $text = Get-Content -LiteralPath '.env.example' -Raw
    $text = [regex]::Replace($text, 'CHANGE_ME', { [Convert]::ToHexString([System.Security.Cryptography.RandomNumberGenerator]::GetBytes(24)).ToLowerInvariant() })
    [IO.File]::WriteAllText((Join-Path (Get-Location) '.env'), $text)
    Write-Host 'Создан локальный .env со случайными паролями. Учётные данные администратора находятся в этом файле.'
}
if ($SkipStart) { exit 0 }
docker compose up -d --wait
if ($LASTEXITCODE -ne 0) { throw 'Не удалось запустить WordPress и базу данных.' }
docker compose run --rm --entrypoint sh cli /opt/vniipo/bootstrap.sh
if ($LASTEXITCODE -ne 0) { throw 'Не удалось инициализировать сайт.' }
Write-Host 'Готово: http://localhost:8088 (или SITE_URL из .env)'
