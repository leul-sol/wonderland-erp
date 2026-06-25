# Backup all Wonderland MySQL databases (wh_s1_db … wh_s4_db)

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot
. (Join-Path $PSScriptRoot "lib\MySqlContainer.ps1")

$BackupDir = Join-Path $RepoRoot "backups"
New-Item -ItemType Directory -Force -Path $BackupDir | Out-Null

Import-RepoEnv -Root $RepoRoot

$retention = if ($env:BACKUP_RETENTION_DAYS) { [int]$env:BACKUP_RETENTION_DAYS } else { 14 }
$mysqlRoot = if ($env:MYSQL_ROOT_PASSWORD) { $env:MYSQL_ROOT_PASSWORD } else { "root_secret" }
$stamp = (Get-Date).ToUniversalTime().ToString("yyyyMMddTHHmmssZ")

Write-Host "Running MySQL backup (retention ${retention} days)..."

$shell = @"
set -eu
stamp='$stamp'
work=`$(mktemp -d)
for db in wh_s1_db wh_s2_db wh_s3_db wh_s4_db; do
  echo "[backup] dumping `$db"
  mysqldump -h localhost -uroot -p"`$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers --databases "`$db" > "`$work/`$db.sql"
done
tar -czf "/backups/wonderland-mysql-`${stamp}.tar.gz" -C "`$work" .
rm -rf "`$work"
find /backups -type f -name 'wonderland-mysql-*.tar.gz' -mtime +$retention -delete 2>/dev/null || true
echo "[backup] wrote /backups/wonderland-mysql-`${stamp}.tar.gz"
"@

$exitCode = Invoke-MySqlInlineScript -Script $shell -Environment @{ MYSQL_ROOT_PASSWORD = $mysqlRoot }
if ($exitCode -ne 0) {
    Write-Error "Backup failed. Ensure wh-mysql is running with ./backups mounted."
}

$latest = Get-ChildItem $BackupDir -Filter "wonderland-mysql-*.tar.gz" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
if ($null -eq $latest) {
    Write-Error "No backup file found in $BackupDir"
}

Write-Host "Backup complete: $($latest.FullName)" -ForegroundColor Green
