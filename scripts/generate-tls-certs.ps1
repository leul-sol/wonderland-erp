# Generate self-signed TLS cert for gateway/certs/ (staging / lab only)

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
$CertsDir = Join-Path $RepoRoot "gateway\certs"
New-Item -ItemType Directory -Force -Path $CertsDir | Out-Null

$fullchain = Join-Path $CertsDir "fullchain.pem"
$privkey = Join-Path $CertsDir "privkey.pem"

if (Get-Command openssl -ErrorAction SilentlyContinue) {
    openssl req -x509 -nodes -days 825 -newkey rsa:2048 `
        -keyout $privkey -out $fullchain `
        -subj "/CN=localhost/O=Wonderland Hotel/C=ET"
} else {
    Write-Host "OpenSSL not found locally — generating via Docker..."
    docker run --rm -v "${CertsDir}:/certs" alpine/openssl req -x509 -nodes -days 825 -newkey rsa:2048 `
        -keyout /certs/privkey.pem -out /certs/fullchain.pem `
        -subj "/CN=localhost/O=Wonderland Hotel/C=ET"
}

Write-Host "Created:" -ForegroundColor Green
Write-Host "  $fullchain"
Write-Host "  $privkey"
Write-Host ""
Write-Host "Start with TLS:"
Write-Host "  docker compose -f docker-compose.yml -f docker-compose.tls.yml up -d"
