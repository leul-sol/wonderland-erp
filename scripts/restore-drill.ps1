param(
    [string]$Archive = ""
)

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot
. (Join-Path $PSScriptRoot "lib\MySqlContainer.ps1")

Import-RepoEnv -Root $RepoRoot

$mysqlRoot = if ($env:MYSQL_ROOT_PASSWORD) { $env:MYSQL_ROOT_PASSWORD } else { "root_secret" }
$args = @()

if ($Archive -ne "") {
    $args += "/backups/$(Split-Path $Archive -Leaf)"
}

Write-Host "Running restore drill (non-destructive)..."

$drillOutput = Invoke-MySqlContainerScript `
    -RepoRoot $RepoRoot `
    -RelativeScript "ops\backup\restore-drill.sh" `
    -Arguments $args `
    -Environment @{ MYSQL_ROOT_PASSWORD = $mysqlRoot } `
    -CaptureOutput

if ($drillOutput.ExitCode -ne 0) {
    Write-Error "Restore drill failed."
}

$resultLine = $drillOutput.Output | Where-Object { $_ -match '^DRILL_RESULT ' } | Select-Object -Last 1
if ($resultLine -match 'archive=(\S+)\s+tables=(\d+)') {
    $archiveName = $Matches[1]
    $tableCount = $Matches[2]

    Write-Host "Logging dr.restore_drill audit entry to S1..."
    docker compose exec -T s1-identity php artisan audit:dr-restore-drill $archiveName $tableCount | Out-Null
}

Write-Host "Restore drill passed." -ForegroundColor Green
