# Reset local UAT state for a clean E2E rerun (stack must be running).

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot

Write-Host "Syncing UAT and RTM catalogs..."
docker compose exec -T s4-finance-bi php artisan app:ensure-seeded 2>&1 | Out-Host

Write-Host "Resetting UAT scenario statuses to pending..."
docker compose exec -T s4-finance-bi php artisan uat:reset-scenarios 2>&1 | Out-Host

Write-Host "Resetting S3 room availability..."
docker compose exec -T s3-hospitality php artisan hospitality:reset-rooms 2>&1 | Out-Host

Write-Host "UAT reset complete. Run: .\scripts\run-uat-e2e.ps1"
