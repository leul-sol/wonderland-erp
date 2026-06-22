# Start Wonderland ERP (requires Docker Desktop running)

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot

if (-not (Test-Path "s1-identity-access\.env")) {
    Copy-Item "s1-identity-access/.env.example" "s1-identity-access/.env"
    Write-Host "Created s1-identity-access/.env from .env.example"
}

if (-not (Test-Path "s4-finance-bi\.env")) {
    Copy-Item "s4-finance-bi/.env.example" "s4-finance-bi/.env"
    Write-Host "Created s4-finance-bi/.env from .env.example"
}

if (-not (Test-Path "s3-hospitality-operations\.env")) {
    Copy-Item "s3-hospitality-operations/.env.example" "s3-hospitality-operations/.env"
    Write-Host "Created s3-hospitality-operations/.env from .env.example"
}

Write-Host "Building and starting containers..."
docker compose up -d --build

Write-Host "Waiting for MySQL, S1, S4, and S3..."
$ready = $false
for ($i = 0; $i -lt 45; $i++) {
    # Native commands (mysqladmin) write warnings to stderr; do not treat as fatal.
    $prevPreference = $ErrorActionPreference
    $ErrorActionPreference = "Continue"

    docker compose exec -T wh-mysql mysqladmin ping -h localhost -uroot -proot_secret --silent 2>$null | Out-Null
    $mysqlOk = ($LASTEXITCODE -eq 0)

    docker compose exec -T s1-identity php artisan migrate:status 2>$null | Out-Null
    $s1Ok = ($LASTEXITCODE -eq 0)

    docker compose exec -T s4-finance-bi php artisan migrate:status 2>$null | Out-Null
    $s4Ok = ($LASTEXITCODE -eq 0)

    docker compose exec -T s3-hospitality php artisan migrate:status 2>$null | Out-Null
    $s3Ok = ($LASTEXITCODE -eq 0)

    $ErrorActionPreference = $prevPreference

    if ($mysqlOk -and $s1Ok -and $s4Ok -and $s3Ok) {
        $ready = $true
        break
    }

    Start-Sleep -Seconds 2
}

if (-not $ready) {
    Write-Warning "Services still starting. Run bootstrap manually if login fails."
}

Write-Host "Running database migrations (seed only if admin missing)..."
$prevPreference = $ErrorActionPreference
$ErrorActionPreference = "Continue"
docker compose exec -T s1-identity php artisan app:ensure-seeded 2>&1 | Out-Host
$seedOk = ($LASTEXITCODE -eq 0)
$ErrorActionPreference = $prevPreference

if (-not $seedOk) {
    Write-Warning "S1 bootstrap failed. Retry: docker compose exec s1-identity php artisan app:ensure-seeded"
}

$ErrorActionPreference = "Continue"
docker compose exec -T s4-finance-bi php artisan app:ensure-seeded 2>&1 | Out-Host
$s4SeedOk = ($LASTEXITCODE -eq 0)
$ErrorActionPreference = $prevPreference

if (-not $s4SeedOk) {
    Write-Warning "S4 bootstrap failed. Retry: docker compose exec s4-finance-bi php artisan app:ensure-seeded"
}

$ErrorActionPreference = "Continue"
docker compose exec -T s3-hospitality php artisan app:ensure-seeded 2>&1 | Out-Host
$s3SeedOk = ($LASTEXITCODE -eq 0)
$ErrorActionPreference = $prevPreference

if (-not $s3SeedOk) {
    Write-Warning "S3 bootstrap failed. Retry: docker compose exec s3-hospitality php artisan app:ensure-seeded"
}

try {
    $response = Invoke-RestMethod -Uri "http://localhost/s1/api/v1/health" -TimeoutSec 10
    Write-Host "S1 health:" ($response | ConvertTo-Json -Compress)
} catch {
    Write-Host "S1 health check failed (containers may still be starting)."
}

try {
    $s4 = Invoke-RestMethod -Uri "http://localhost/s4/api/v1/health" -TimeoutSec 10
    Write-Host "S4 health:" ($s4 | ConvertTo-Json -Compress)
} catch {
    Write-Host "S4 health check failed. Run:"
    Write-Host "  docker compose exec s4-finance-bi php artisan app:ensure-seeded"
    Write-Host "  curl http://localhost/s4/api/v1/health"
}

try {
    $s3 = Invoke-RestMethod -Uri "http://localhost/s3/api/v1/health" -TimeoutSec 10
    Write-Host "S3 health:" ($s3 | ConvertTo-Json -Compress)
} catch {
    Write-Host "S3 health check failed. Run:"
    Write-Host "  docker compose exec s3-hospitality php artisan app:ensure-seeded"
    Write-Host "  curl http://localhost/s3/api/v1/health"
}

Write-Host "Done."
