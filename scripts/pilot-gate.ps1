# Wonderland ERP - pilot gate (NOT production sign-off)
# Runs: service tests -> traceability summary -> UAT E2E
# Exit 0 only when tests pass, no critical SDD gaps, UAT 24/24

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot

function Write-Step([string]$Message) {
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Get-TraceabilitySummary {
    $matrixPath = Join-Path $RepoRoot "specs\traceability\matrix.yaml"
    if (-not (Test-Path $matrixPath)) {
        throw "Missing traceability matrix: $matrixPath"
    }

    $lines = Get-Content $matrixPath
    $requirements = @()
    foreach ($line in $lines) {
        if ($line -match '^\s*-\s*\{\s*key:\s*([^,]+)') {
            $block = $line
            if ($block -match 'status:\s*(\w+)') { $status = $Matches[1] } else { continue }
            if ($block -match 'priority:\s*(\w+)') { $priority = $Matches[1] } else { $priority = "medium" }
            if ($block -match 'key:\s*([^,]+)') { $key = $Matches[1].Trim() } else { continue }
            $requirements += [pscustomobject]@{ Key = $key; Status = $status; Priority = $priority }
        }
    }

    $total = $requirements.Count
    $implemented = ($requirements | Where-Object { $_.Status -eq "implemented" }).Count
    $partial = ($requirements | Where-Object { $_.Status -eq "partial" }).Count
    $missing = ($requirements | Where-Object { $_.Status -eq "missing" }).Count
    $criticalMissing = $requirements | Where-Object { $_.Status -eq "missing" -and $_.Priority -eq "critical" }

    return [pscustomobject]@{
        Total           = $total
        Implemented     = $implemented
        Partial         = $partial
        Missing         = $missing
        CriticalMissing = $criticalMissing
    }
}

function Get-PilotReadinessSummary {
    $path = Join-Path $RepoRoot "specs\traceability\pilot-readiness.yaml"
    $missing = @()
    $content = Get-Content $path -Raw
    foreach ($m in [regex]::Matches($content, 'id:\s*(\S+)[\s\S]*?status:\s*(missing|partial)')) {
        $missing += "$($m.Groups[1].Value) ($($m.Groups[2].Value))"
    }
    return $missing
}

$failed = $false

Write-Step "SDD traceability matrix"
$trace = Get-TraceabilitySummary
Write-Host "  Requirements: $($trace.Total) | implemented: $($trace.Implemented) | partial: $($trace.Partial) | missing: $($trace.Missing)"
if ($trace.CriticalMissing.Count -gt 0) {
    Write-Host "  BLOCKED - critical missing:" -ForegroundColor Red
    $trace.CriticalMissing | ForEach-Object { Write-Host "    - $($_.Key)" -ForegroundColor Red }
    $failed = $true
} else {
    Write-Host "  No critical SDD gaps marked missing." -ForegroundColor Green
}

Write-Step "Pilot readiness (backup / monitoring / support)"
$openItems = Get-PilotReadinessSummary
Write-Host "  Open pilot-readiness items: $($openItems.Count) (expected - not production yet)"
foreach ($item in $openItems | Select-Object -First 8) {
    Write-Host "    - $item"
}
if ($openItems.Count -gt 8) {
    Write-Host "    ... and $($openItems.Count - 8) more (see specs/traceability/pilot-readiness.yaml)"
}

Write-Step "Automated tests (S1-S4)"
$services = @("s1-identity", "s2-workforce", "s3-hospitality", "s4-finance-bi")
foreach ($svc in $services) {
    Write-Host "  $svc ..."
    docker compose exec $svc php artisan test --no-ansi 2>&1 | Out-Host
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  FAILED: $svc tests" -ForegroundColor Red
        $failed = $true
    }
}

Write-Step "UAT E2E (24 scenarios)"
& (Join-Path $RepoRoot "scripts\run-uat-e2e.ps1")
if ($LASTEXITCODE -ne 0) {
    Write-Host "  UAT FAILED" -ForegroundColor Red
    $failed = $true
}

Write-Step "Pilot gate verdict"
if ($failed) {
    Write-Host "  PILOT GATE: FAILED - fix blockers before pilot or UI work." -ForegroundColor Red
    exit 1
}

Write-Host "  PILOT GATE: PASSED (MVP ready for controlled pilot - NOT production)." -ForegroundColor Green
Write-Host "  Next: hotel dry-run, close pilot-readiness items, then UI scoping from matrix."
exit 0
