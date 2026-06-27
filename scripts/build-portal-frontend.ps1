# Build portal Vue assets via Docker (avoids Windows npm + WSL UNC path issues)

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
$Portal = Join-Path $RepoRoot "web-portal"

Write-Host "Building web-portal frontend in Node container..."
docker run --rm `
  -v "${Portal}:/app" `
  -w /app `
  node:22-bookworm `
  bash -lc "rm -rf node_modules && npm install --legacy-peer-deps && node ./node_modules/vite/bin/vite.js build"

if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

Write-Host "Done. Assets in web-portal/public/build/"
Write-Host "Restart portal: docker compose restart web-portal"
