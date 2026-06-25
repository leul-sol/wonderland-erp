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

$exitCode = Invoke-MySqlContainerScript `
    -RepoRoot $RepoRoot `
    -RelativeScript "ops\backup\restore-drill.sh" `
    -Arguments $args `
    -Environment @{ MYSQL_ROOT_PASSWORD = $mysqlRoot }

if ($exitCode -ne 0) {
    Write-Error "Restore drill failed."
}

Write-Host "Restore drill passed." -ForegroundColor Green
