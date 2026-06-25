# Stack health, worker containers, failed outbox / jobs. Exit 1 on failure.

$ErrorActionPreference = "Continue"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot

function Import-RepoEnv {
    $path = Join-Path $RepoRoot ".env"
    if (-not (Test-Path $path)) { return }
    Get-Content $path | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq "" -or $line.StartsWith("#")) { return }
        $eq = $line.IndexOf("=")
        if ($eq -lt 1) { return }
        $name = $line.Substring(0, $eq).Trim()
        $value = $line.Substring($eq + 1).Trim().Trim('"').Trim("'")
        Set-Item -Path "env:$name" -Value $value
    }
}

Import-RepoEnv

$failed = $false
$gatewayHost = if ($env:GATEWAY_HOST) { $env:GATEWAY_HOST } else { "127.0.0.1" }
$base = "http://${gatewayHost}"

function Test-Endpoint {
    param(
        [string]$Name,
        [string]$Url
    )

    try {
        $null = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 30
        Write-Host "OK   $Name" -ForegroundColor Green
    } catch {
        Write-Host "FAIL $Name - $($_.Exception.Message)" -ForegroundColor Red
        $script:failed = $true
    }
}

Write-Host "==> HTTP health" -ForegroundColor Cyan
Test-Endpoint -Name "gateway /health" -Url "$base/health"
Test-Endpoint -Name "S1 health" -Url "$base/s1/api/v1/health"
Test-Endpoint -Name "S2 health" -Url "$base/s2/api/v1/health"
Test-Endpoint -Name "S3 health" -Url "$base/s3/api/v1/health"
Test-Endpoint -Name "S4 health" -Url "$base/s4/api/v1/health"
Test-Endpoint -Name "portal /up" -Url "$base/up"

Write-Host ""
Write-Host "==> Worker containers" -ForegroundColor Cyan
$workers = @("s1-workers", "s2-workers", "s3-workers", "s4-workers")
foreach ($svc in $workers) {
    $state = docker compose ps $svc --format "{{.State}}" 2>$null
    if ($state -match "running") {
        Write-Host "OK   $svc" -ForegroundColor Green
    } else {
        Write-Host "FAIL $svc state=$state" -ForegroundColor Red
        $script:failed = $true
    }
}

Write-Host ""
Write-Host "==> Failed outbox / jobs (MySQL)" -ForegroundColor Cyan
$mysqlRoot = if ($env:MYSQL_ROOT_PASSWORD) { $env:MYSQL_ROOT_PASSWORD } else { "root_secret" }
$dbs = @("wh_s1_db", "wh_s2_db", "wh_s3_db", "wh_s4_db")

foreach ($db in $dbs) {
    $sql = "SELECT COUNT(*) FROM {0}.event_outbox WHERE status='failed';" -f $db
    $outbox = docker compose exec -T wh-mysql mysql -uroot "-p$mysqlRoot" -N -e $sql 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "WARN ${db} event_outbox not queryable" -ForegroundColor Yellow
        continue
    }
    $count = [int]($outbox.Trim())
    if ($count -ge 1) {
        Write-Host "FAIL ${db} outbox failed=$count" -ForegroundColor Red
        $script:failed = $true
    } else {
        Write-Host "OK   ${db} outbox failed=0" -ForegroundColor Green
    }
}

if ($failed) {
    Write-Host ""
    Write-Host "Monitor: ALERT - see ops/runbooks/incident-response.md" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Monitor: OK" -ForegroundColor Green
exit 0
