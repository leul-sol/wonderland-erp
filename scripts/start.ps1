# Start Wonderland ERP (requires Docker Desktop running)

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot

if (-not (Test-Path "s1-identity-access\.env")) {
    Copy-Item "s1-identity-access/.env.example" "s1-identity-access/.env"
    Write-Host "Created s1-identity-access/.env from .env.example"
}

Write-Host "Building and starting containers..."
docker compose up -d --build

Write-Host "Waiting for MySQL and S1..."
$ready = $false
for ($i = 0; $i -lt 30; $i++) {
    # Native commands (mysqladmin) write warnings to stderr; do not treat as fatal.
    $prevPreference = $ErrorActionPreference
    $ErrorActionPreference = "Continue"

    docker compose exec -T wh-mysql mysqladmin ping -h localhost -uroot -proot_secret --silent 2>$null | Out-Null
    $mysqlOk = ($LASTEXITCODE -eq 0)

    docker compose exec -T s1-identity php artisan migrate:status 2>$null | Out-Null
    $s1Ok = ($LASTEXITCODE -eq 0)

    $ErrorActionPreference = $prevPreference

    if ($mysqlOk -and $s1Ok) {
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
    Write-Warning "Bootstrap failed. Retry: docker compose exec s1-identity php artisan app:ensure-seeded"
}

try {
    $response = Invoke-RestMethod -Uri "http://localhost/s1/api/v1/health" -TimeoutSec 10
    Write-Host "S1 health:" ($response | ConvertTo-Json -Compress)
} catch {
    Write-Host "Health check failed (containers may still be starting). Run:"
    Write-Host "  docker compose exec s1-identity php artisan app:ensure-seeded"
    Write-Host "  curl http://localhost/s1/api/v1/health"
}

Write-Host "Done."
