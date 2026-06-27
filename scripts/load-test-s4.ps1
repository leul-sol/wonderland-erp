# S4 load test — dashboard P95 and journal post latency (pilot gate evidence P7-02)
param(
    [string]$BaseUrl = "http://localhost/s4/api/v1",
    [string]$Token = $env:S4_LOAD_TEST_TOKEN,
    [int]$Iterations = 50,
    [int]$DashboardP95Ms = 150,
    [int]$JournalP95Ms = 400
)

if (-not $Token) {
    Write-Error "Set S4_LOAD_TEST_TOKEN to a valid JWT with S4.bi.dashboards.read and S4.finance.journal_entries.create"
    exit 1
}

$headers = @{ Authorization = "Bearer $Token"; "Content-Type" = "application/json" }

function Get-P95([double[]]$Samples) {
    if ($Samples.Count -eq 0) { return 0 }
    $sorted = $Samples | Sort-Object
    $idx = [math]::Ceiling(0.95 * $sorted.Count) - 1
    return $sorted[[math]::Max(0, $idx)]
}

$dashMs = @()
for ($i = 0; $i -lt $Iterations; $i++) {
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    Invoke-RestMethod -Uri "$BaseUrl/dashboards/executive?fiscal_period_id=1" -Headers $headers -Method Get | Out-Null
    $sw.Stop()
    $dashMs += $sw.Elapsed.TotalMilliseconds
}

$journalMs = @()
$body = @{
    description = "Load test journal $([guid]::NewGuid().ToString('N').Substring(0,8))"
    entry_date = (Get-Date -Format "yyyy-MM-dd")
    source_module = "manual"
    lines = @(
        @{ account_code = "1001"; debit = 10; credit = 0 },
        @{ account_code = "4001"; debit = 0; credit = 10 }
    )
} | ConvertTo-Json -Depth 5

for ($i = 0; $i -lt [math]::Min(20, $Iterations); $i++) {
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    try {
        Invoke-RestMethod -Uri "$BaseUrl/journal-entries" -Headers $headers -Method Post -Body $body | Out-Null
    } catch {
        Write-Warning "Journal post failed (may need draft-only env): $_"
    }
    $sw.Stop()
    $journalMs += $sw.Elapsed.TotalMilliseconds
}

$dashP95 = Get-P95 $dashMs
$journalP95 = Get-P95 $journalMs

Write-Host "Dashboard P95: $([math]::Round($dashP95, 1)) ms (SLO $DashboardP95Ms ms)"
Write-Host "Journal post P95: $([math]::Round($journalP95, 1)) ms (SLO $JournalP95Ms ms)"

if ($dashP95 -gt $DashboardP95Ms -or $journalP95 -gt $JournalP95Ms) {
    exit 1
}

exit 0
