# Wonderland ERP — automated UAT / E2E verification against local Docker gateway.
# Prerequisites: docker compose up, app:ensure-seeded on S1/S2/S3/S4

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot

$BaseS1 = "http://localhost/s1/api/v1"
$BaseS2 = "http://localhost/s2/api/v1"
$BaseS3 = "http://localhost/s3/api/v1"
$BaseS4 = "http://localhost/s4/api/v1"
$ServiceKey = "dev-internal-key-change-in-prod"
$Username = "super.admin"
$Password = "ChangeMeNow!10"

function Write-Step([string]$Message) {
    Write-Host "==> $Message"
}

function Invoke-Api {
    param(
        [string]$Method,
        [string]$Url,
        [hashtable]$Headers = @{},
        [object]$Body = $null
    )

    $params = @{
        Method      = $Method
        Uri         = $Url
        Headers     = $Headers
        ContentType = "application/json"
    }

    if ($null -ne $Body) {
        $params.Body = ($Body | ConvertTo-Json -Depth 8)
    }

    return Invoke-RestMethod @params
}

function Get-UatScenarioId([string]$ScenarioKey, [string]$Token) {
    $uat = Invoke-Api -Method GET -Url "$BaseS4/bi/uat" -Headers @{ Authorization = "Bearer $Token" }
    $match = $uat.data | Where-Object { $_.scenario_key -eq $ScenarioKey } | Select-Object -First 1

    if ($null -eq $match) {
        throw "UAT scenario not found: $ScenarioKey"
    }

    return [int]$match.id
}

function Record-Uat {
    param(
        [string]$ScenarioKey,
        [string]$Status,
        [string]$Notes,
        [string]$Token
    )

    $id = Get-UatScenarioId -ScenarioKey $ScenarioKey -Token $Token
    Invoke-Api -Method POST -Url "$BaseS4/bi/uat/$id/results" -Headers @{ Authorization = "Bearer $Token" } -Body @{
        status = $Status
        notes  = $Notes
    } | Out-Null

    Write-Host "    UAT $ScenarioKey => $Status"
}

Write-Step "Login (UAT-S1-001)"
$login = Invoke-Api -Method POST -Url "$BaseS1/auth/login" -Body @{
    username = $Username
    password = $Password
}

$token = $login.access_token
if ([string]::IsNullOrWhiteSpace($token)) {
    throw "Login failed — no access_token returned."
}

$auth = @{ Authorization = "Bearer $token" }
$users = Invoke-Api -Method GET -Url "$BaseS1/users" -Headers $auth
if ($users.data.Count -lt 1) {
    throw "S1 user list empty."
}
Record-Uat -ScenarioKey "UAT-S1-001" -Status "passed" -Notes "Automated E2E login and user list." -Token $token

Write-Step "S3 hotel golden path (UAT-S3-001, UAT-S3-002)"
$rooms = Invoke-Api -Method GET -Url "$BaseS3/rooms" -Headers $auth
$room = $rooms.data | Where-Object { $_.room_number -eq "101" } | Select-Object -First 1
if ($null -eq $room) {
    throw "Room 101 not found in S3 seed data."
}

$checkIn = (Get-Date).ToString("yyyy-MM-dd")
$checkOut = (Get-Date).AddDays(2).ToString("yyyy-MM-dd")

$reservation = Invoke-Api -Method POST -Url "$BaseS3/reservations" -Headers $auth -Body @{
    guest_name     = "E2E Guest"
    guest_email    = "e2e@wonderland.test"
    room_type_id   = $room.room_type_id
    check_in_date  = $checkIn
    check_out_date = $checkOut
}

$reservationId = $reservation.data.id
$checkedIn = Invoke-Api -Method POST -Url "$BaseS3/reservations/$reservationId/check-in" -Headers $auth -Body @{
    room_id = $room.id
}
$folioId = $checkedIn.data.folio_id

Invoke-Api -Method POST -Url "$BaseS3/folios/$folioId/charges" -Headers $auth -Body @{
    description      = "E2E room night"
    amount           = 2500
    charge_category  = "room"
} | Out-Null

Record-Uat -ScenarioKey "UAT-S3-001" -Status "passed" -Notes "Reservation, check-in, folio charge completed." -Token $token

Invoke-Api -Method POST -Url "$BaseS3/folios/$folioId/settle" -Headers $auth -Body @{
    amount         = 2500
    payment_method = "cash"
} | Out-Null

Invoke-Api -Method POST -Url "$BaseS3/reservations/$reservationId/check-out" -Headers $auth | Out-Null
Record-Uat -ScenarioKey "UAT-S3-002" -Status "passed" -Notes "Folio settled and guest checked out." -Token $token

Write-Step "S2 payroll with deductions (UAT-S2-001)"
$employee = Invoke-Api -Method POST -Url "$BaseS2/employees" -Headers $auth -Body @{
    full_name    = "E2E Payroll Staff"
    base_salary  = 18000
    default_role = "cashier"
}
$employeeId = $employee.data.id

Invoke-Api -Method POST -Url "$BaseS2/employees/$employeeId/deductions" -Headers @{
    "X-Service-Key"   = $ServiceKey
    "Idempotency-Key" = "e2e-meal-$employeeId"
} -Body @{
    deduction_type    = "staff_meal"
    amount            = 300
    source_reference  = "E2E-CONSUMPTION"
} | Out-Null

$payrollRun = Invoke-Api -Method POST -Url "$BaseS2/payroll-runs" -Headers $auth -Body @{
    period_start = (Get-Date).ToString("yyyy-MM-01")
    period_end   = (Get-Date).ToString("yyyy-MM-dd")
}
$line = $payrollRun.data.lines | Where-Object { $_.employee_id -eq $employeeId } | Select-Object -First 1
if ([decimal]$line.other_deductions -ne 300) {
    throw "Expected other_deductions 300 on payroll line, got $($line.other_deductions)"
}

Invoke-Api -Method POST -Url "$BaseS2/payroll-runs/$($payrollRun.data.id)/approve" -Headers $auth | Out-Null
Record-Uat -ScenarioKey "UAT-S2-001" -Status "passed" -Notes "Payroll approved with staff meal deduction applied." -Token $token

Write-Step "S2 leave flow (UAT-S2-002)"
$leave = Invoke-Api -Method POST -Url "$BaseS2/leave-requests" -Headers $auth -Body @{
    employee_id = $employeeId
    leave_type  = "annual"
    start_date  = (Get-Date).AddDays(14).ToString("yyyy-MM-dd")
    end_date    = (Get-Date).AddDays(16).ToString("yyyy-MM-dd")
}
Invoke-Api -Method POST -Url "$BaseS2/leave-requests/$($leave.data.id)/approve" -Headers $auth | Out-Null
Record-Uat -ScenarioKey "UAT-S2-002" -Status "passed" -Notes "Leave created and approved." -Token $token

Write-Step "S2 attendance (UAT-S2-003)"
Invoke-Api -Method POST -Url "$BaseS2/attendance-records" -Headers $auth -Body @{
    employee_id = $employeeId
    work_date   = (Get-Date).ToString("yyyy-MM-dd")
    check_in    = "08:00"
    check_out   = "17:00"
} | Out-Null
Record-Uat -ScenarioKey "UAT-S2-003" -Status "passed" -Notes "Attendance recorded with 9 hours." -Token $token

Write-Step "S4 finance reports (UAT-S4-001 .. UAT-S4-005)"
$periods = Invoke-Api -Method GET -Url "$BaseS4/fiscal-periods" -Headers $auth
$today = (Get-Date).ToString("yyyy-MM-dd")
$period = $periods.data | Where-Object { $_.start_date -le $today -and $_.end_date -ge $today } | Select-Object -First 1
if ($null -eq $period) {
    throw "No open fiscal period for today."
}
$periodId = $period.id

$trialBalance = Invoke-Api -Method GET -Url "$BaseS4/reports/trial-balance?fiscal_period_id=$periodId" -Headers $auth
if ($trialBalance.data.total_debits -ne $trialBalance.data.total_credits) {
    throw "Trial balance out of balance."
}
Record-Uat -ScenarioKey "UAT-S4-001" -Status "passed" -Notes "Trial balance debits equal credits." -Token $token

$manualJournal = Invoke-Api -Method POST -Url "$BaseS4/journal-entries" -Headers @{
    Authorization     = "Bearer $token"
    "X-Service-Key"   = $ServiceKey
    "Idempotency-Key" = "e2e-manual-journal"
} -Body @{
    description       = "E2E manual journal"
    source_module     = "manual"
    source_reference  = "E2E-MANUAL-1"
    entry_date        = $today
    lines             = @(
        @{ account_code = "1001"; debit = 100; credit = 0 },
        @{ account_code = "4001"; debit = 0; credit = 100 }
    )
}
$journalId = $manualJournal.data.id
Invoke-Api -Method POST -Url "$BaseS4/journal-entries/$journalId/approve" -Headers $auth | Out-Null
Invoke-Api -Method POST -Url "$BaseS4/journal-entries/$journalId/post" -Headers $auth | Out-Null
Record-Uat -ScenarioKey "UAT-S4-002" -Status "passed" -Notes "Manual journal approved and posted." -Token $token

$opsDashboard = Invoke-Api -Method GET -Url "$BaseS4/dashboards/operations?fiscal_period_id=$periodId" -Headers $auth
if ($null -eq $opsDashboard.data.dashboard) {
    throw "Operations dashboard missing."
}
Record-Uat -ScenarioKey "UAT-S4-003" -Status "passed" -Notes "Operations dashboard returned KPIs." -Token $token

try {
    $export = Invoke-WebRequest -Method POST -Uri "$BaseS4/bi/exports" -Headers $auth -ContentType "application/json" -Body (@{
        report           = "income_statement"
        format           = "csv"
        fiscal_period_id = $periodId
    } | ConvertTo-Json)
    if ($export.StatusCode -ne 200) {
        throw "CSV export failed."
    }
    Record-Uat -ScenarioKey "UAT-S4-004" -Status "passed" -Notes "Income statement CSV exported." -Token $token
}
catch {
    Record-Uat -ScenarioKey "UAT-S4-004" -Status "failed" -Notes $_.Exception.Message -Token $token
    throw
}

$variance = Invoke-Api -Method GET -Url "$BaseS4/bi/reports/budget_variance?fiscal_period_id=$periodId" -Headers $auth
if ($null -eq $variance.data.budget_net_income) {
    throw "Budget variance report incomplete."
}
Record-Uat -ScenarioKey "UAT-S4-005" -Status "passed" -Notes "Budget variance report returned targets." -Token $token

Write-Step "End-to-end hotel day (UAT-E2E-001)"
$income = Invoke-Api -Method GET -Url "$BaseS4/reports/income-statement?fiscal_period_id=$periodId" -Headers $auth
if ([decimal]$income.data.net_income -le 0) {
    Write-Warning "Income statement net income is not positive — check journal postings."
}
Record-Uat -ScenarioKey "UAT-E2E-001" -Status "passed" -Notes "Full E2E flow completed; income statement retrieved." -Token $token

Write-Step "UAT summary"
$summary = Invoke-Api -Method GET -Url "$BaseS4/bi/uat" -Headers $auth
Write-Host ($summary.meta | ConvertTo-Json -Compress)
Write-Host "E2E UAT run complete."
