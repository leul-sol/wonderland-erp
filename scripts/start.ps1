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

if (-not (Test-Path "s2-workforce-payroll\.env")) {
    Copy-Item "s2-workforce-payroll/.env.example" "s2-workforce-payroll/.env"
    Write-Host "Created s2-workforce-payroll/.env from .env.example"
}

if (-not (Test-Path ".env.example")) {
    Write-Warning "Missing root .env.example - secrets template not found."
}

function Import-RepoEnv {
    param([string]$Root)

    $path = Join-Path $Root ".env"
    if (-not (Test-Path $path)) {
        return
    }

    Get-Content $path | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq "" -or $line.StartsWith("#")) {
            return
        }

        $eq = $line.IndexOf("=")
        if ($eq -lt 1) {
            return
        }

        $name = $line.Substring(0, $eq).Trim()
        $value = $line.Substring($eq + 1).Trim().Trim('"').Trim("'")
        Set-Item -Path "env:$name" -Value $value
    }
}

Import-RepoEnv -Root $RepoRoot

$MysqlRootPassword = if ($env:MYSQL_ROOT_PASSWORD) { $env:MYSQL_ROOT_PASSWORD } else { "root_secret" }
$DbPassword = if ($env:DB_PASSWORD) { $env:DB_PASSWORD } else { "wh_app_secret" }
$InternalKey = if ($env:INTERNAL_KEY_CURRENT) { $env:INTERNAL_KEY_CURRENT } else { "dev-internal-key-change-in-prod" }

function Set-EnvFileLine {
    param(
        [string]$FilePath,
        [string]$Key,
        [string]$Value
    )

    if (-not (Test-Path $FilePath)) {
        return
    }

    $escaped = $Value -replace '\\', '\\\\'
    $lines = Get-Content $FilePath
    $found = $false
    $updated = foreach ($line in $lines) {
        if ($line -match "^\s*$([regex]::Escape($Key))\s*=") {
            $found = $true
            "$Key=$escaped"
        } else {
            $line
        }
    }

    if (-not $found) {
        $updated = @($updated) + "$Key=$escaped"
    }

    Set-Content -Path $FilePath -Value $updated -Encoding utf8
}

$ServiceEnvFiles = @(
    "s1-identity-access\.env",
    "s2-workforce-payroll\.env",
    "s3-hospitality-operations\.env",
    "s4-finance-bi\.env"
)

foreach ($relPath in $ServiceEnvFiles) {
    $fullPath = Join-Path $RepoRoot $relPath
    Set-EnvFileLine -FilePath $fullPath -Key "DB_PASSWORD" -Value $DbPassword
    Set-EnvFileLine -FilePath $fullPath -Key "INTERNAL_KEY_CURRENT" -Value $InternalKey
}

Write-Host "Synced DB_PASSWORD and INTERNAL_KEY_CURRENT from root .env into service .env files."

Write-Host "Building and starting containers..."
docker compose up -d --build

Write-Host "Waiting for MySQL init and app DB access..."
$ready = $false
for ($i = 0; $i -lt 60; $i++) {
    $prevPreference = $ErrorActionPreference
    $ErrorActionPreference = "Continue"

    docker compose exec -T wh-mysql mysqladmin ping -h localhost -uroot "-p$MysqlRootPassword" --silent 2>$null | Out-Null
    $mysqlOk = ($LASTEXITCODE -eq 0)

    $appDbOk = $false
    if ($mysqlOk) {
        docker compose exec -T wh-mysql mysql -uwh_app "-p$DbPassword" -e "SELECT 1" wh_s1_db 2>$null | Out-Null
        $appDbOk = ($LASTEXITCODE -eq 0)
    }

    docker compose exec -T s1-identity php artisan migrate:status 2>$null | Out-Null
    $s1Ok = ($LASTEXITCODE -eq 0)

    docker compose exec -T s4-finance-bi php artisan migrate:status 2>$null | Out-Null
    $s4Ok = ($LASTEXITCODE -eq 0)

    docker compose exec -T s3-hospitality php artisan migrate:status 2>$null | Out-Null
    $s3Ok = ($LASTEXITCODE -eq 0)

    docker compose exec -T s2-workforce php artisan migrate:status 2>$null | Out-Null
    $s2Ok = ($LASTEXITCODE -eq 0)

    $ErrorActionPreference = $prevPreference

    if ($mysqlOk -and $appDbOk -and $s1Ok -and $s4Ok -and $s3Ok -and $s2Ok) {
        $ready = $true
        break
    }

    Start-Sleep -Seconds 2
}

if (-not $ready) {
    Write-Warning "Services still starting. Run bootstrap manually if login fails."
    $mysqlState = docker compose ps wh-mysql --format "{{.State}}" 2>$null
    if ($mysqlState -match "exited|dead") {
        Write-Warning "MySQL is not running. Reset with: docker compose down -v && .\scripts\start.ps1"
        Write-Warning "Recent MySQL logs:"
        docker compose logs wh-mysql --tail 15 2>&1 | Out-Host
        exit 1
    }
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

$ErrorActionPreference = "Continue"
docker compose exec -T s2-workforce php artisan app:ensure-seeded 2>&1 | Out-Host
$s2SeedOk = ($LASTEXITCODE -eq 0)
$ErrorActionPreference = $prevPreference

if (-not $s2SeedOk) {
    Write-Warning "S2 bootstrap failed. Retry: docker compose exec s2-workforce php artisan app:ensure-seeded"
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

try {
    $s2 = Invoke-RestMethod -Uri "http://localhost/s2/api/v1/health" -TimeoutSec 10
    Write-Host "S2 health:" ($s2 | ConvertTo-Json -Compress)
} catch {
    Write-Host "S2 health check failed. Run:"
    Write-Host "  docker compose exec s2-workforce php artisan app:ensure-seeded"
    Write-Host "  curl http://localhost/s2/api/v1/health"
}

Write-Host "Done."
Write-Host ""
Write-Host "Login (Postman Auth -> Login):"
Write-Host "  username: super.admin"
Write-Host "  password: SUPER_ADMIN_PASSWORD from root .env (not the Postman default unless you have no root .env)"
Write-Host ""
Write-Host "If you changed DB_PASSWORD or MYSQL_ROOT_PASSWORD, reset MySQL once:"
Write-Host "  docker compose down -v"
Write-Host "  .\scripts\start.ps1"
Write-Host ""
Write-Host "Staging: copy .env.example to .env and rotate secrets before shared hosting."
Write-Host ""
Write-Host "Optional: run automated UAT / E2E verification:"
Write-Host "  .\scripts\run-uat-e2e.ps1"
