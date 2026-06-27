# S4 soak test — concurrent BI report users (pilot gate evidence P7-03)
param(
    [string]$BaseUrl = "http://localhost/s4/api/v1",
    [string]$Token = $env:S4_LOAD_TEST_TOKEN,
    [int]$ConcurrentUsers = 30,
    [int]$DurationSeconds = 60,
    [string]$ReportSlug = "trial_balance"
)

if (-not $Token) {
    Write-Error "Set S4_LOAD_TEST_TOKEN to a valid JWT with S4.bi.reports.read"
    exit 1
}

$headers = @{ Authorization = "Bearer $Token" }
$uri = "$BaseUrl/bi/reports/$ReportSlug`?fiscal_period_id=1"
$errors = [System.Collections.Concurrent.ConcurrentBag[int]]::new()
$jobs = @()

for ($u = 0; $u -lt $ConcurrentUsers; $u++) {
    $jobs += Start-Job -ScriptBlock {
        param($Uri, $Headers, $Until)
        $localErrors = 0
        while ((Get-Date) -lt $Until) {
            try {
                Invoke-RestMethod -Uri $Uri -Headers $Headers -Method Get | Out-Null
            } catch {
                $localErrors++
            }
        }
        return $localErrors
    } -ArgumentList $uri, $headers, (Get-Date).AddSeconds($DurationSeconds)
}

$totalErrors = ($jobs | Wait-Job | Receive-Job | Measure-Object -Sum).Sum
$jobs | Remove-Job -Force

Write-Host "Soak complete: $ConcurrentUsers users for ${DurationSeconds}s, errors=$totalErrors"
if ($totalErrors -gt 0) { exit 1 }
exit 0
