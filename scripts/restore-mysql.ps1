param(
    [Parameter(Mandatory = $true)]
    [string]$Archive,

    [switch]$Force
)

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot
. (Join-Path $PSScriptRoot "lib\MySqlContainer.ps1")

Import-RepoEnv -Root $RepoRoot

$mysqlRoot = if ($env:MYSQL_ROOT_PASSWORD) { $env:MYSQL_ROOT_PASSWORD } else { "root_secret" }
$BackupDir = Join-Path $RepoRoot "backups"
New-Item -ItemType Directory -Force -Path $BackupDir | Out-Null

$resolved = Resolve-Path $Archive -ErrorAction SilentlyContinue
if (-not $resolved) {
    throw "Archive not found: $Archive"
}

$leaf = Split-Path $resolved -Leaf
$target = Join-Path $BackupDir $leaf

if ($resolved.Path -ne $target) {
    Copy-Item $resolved $target -Force
}

if (-not $Force) {
    Write-Host "WARNING: This will DROP and recreate wh_s1_db, wh_s2_db, wh_s3_db, wh_s4_db." -ForegroundColor Yellow
    $confirm = Read-Host "Type RESTORE to continue"
    if ($confirm -ne "RESTORE") {
        Write-Host "Aborted."
        exit 1
    }
}

Write-Host "Restoring from /backups/$leaf ..."

$exitCode = Invoke-MySqlContainerScript `
    -RepoRoot $RepoRoot `
    -RelativeScript "ops\backup\restore-mysql.sh" `
    -Arguments @("/backups/$leaf", "--yes") `
    -Environment @{ MYSQL_ROOT_PASSWORD = $mysqlRoot }

if ($exitCode -ne 0) {
    Write-Error "Restore failed."
}

Write-Host "Restore finished. Restart API services:" -ForegroundColor Green
Write-Host "  docker compose restart s1-identity s2-workforce s3-hospitality s4-finance-bi web-portal"
