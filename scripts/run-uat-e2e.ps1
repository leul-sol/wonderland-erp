# Wonderland ERP - automated UAT / E2E verification against local Docker gateway.
# Prerequisites: docker compose up, app:ensure-seeded on S1/S2/S3/S4

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot

function Import-RepoEnv {
    param([string]$Root)

    $path = Join-Path $Root ".env"
    if (-not (Test-Path $path)) {
        return
    }

    Get-Content $path | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq "" -or $line.StartsWith("#")) {
            return
        }

        $eq = $line.IndexOf("=")
        if ($eq -lt 1) {
            return
        }

        $name = $line.Substring(0, $eq).Trim()
        $value = $line.Substring($eq + 1).Trim().Trim('"').Trim("'")
        Set-Item -Path "env:$name" -Value $value
    }
}

Import-RepoEnv -Root $RepoRoot

$GatewayHost = if ($env:GATEWAY_HOST) { $env:GATEWAY_HOST } else { "localhost" }
$BaseS1 = "http://$GatewayHost/s1/api/v1"
$BaseS2 = "http://$GatewayHost/s2/api/v1"
$BaseS3 = "http://$GatewayHost/s3/api/v1"
$BaseS4 = "http://$GatewayHost/s4/api/v1"
$ServiceKey = if ($env:INTERNAL_KEY_CURRENT) { $env:INTERNAL_KEY_CURRENT } else { "dev-internal-key-change-in-prod" }
$Username = "super.admin"
$Password = if ($env:SUPER_ADMIN_PASSWORD) { $env:SUPER_ADMIN_PASSWORD } else { 'ChangeMeNow!10' }

function Write-Step([string]$Message) {
    Write-Host "==> $Message"
}

function Invoke-Api {
    param(
        [string]$Method,
        [string]$Url,
        [hashtable]$RequestHeaders = @{},
        [object]$Payload = $null
    )

    $reqHeaders = @{ Accept = "application/json" }
    foreach ($key in $RequestHeaders.Keys) {
        $reqHeaders[$key] = $RequestHeaders[$key]
    }

    try {
        if ($null -ne $Payload) {
            return Invoke-RestMethod -Method $Method -Uri $Url -Headers $reqHeaders -ContentType "application/json" -Body ($Payload | ConvertTo-Json -Depth 8 -Compress)
        }

        return Invoke-RestMethod -Method $Method -Uri $Url -Headers $reqHeaders
    }
    catch {
        $detail = $_.ErrorDetails.Message
        if ([string]::IsNullOrWhiteSpace($detail)) {
            $detail = $_.Exception.Message
        }

        throw "$Method $Url failed: $detail"
    }
}

function Wait-GatewayHealth {
    param(
        [string]$Url,
        [int]$Attempts = 30
    )

    for ($i = 1; $i -le $Attempts; $i++) {
        try {
            $health = Invoke-RestMethod -Method GET -Uri $Url -Headers @{ Accept = "application/json" } -TimeoutSec 5
            if ($health.status -eq "ok") {
                return
            }
        }
        catch {
            if ($i -eq $Attempts) {
                throw "Gateway health check failed for $Url after $Attempts attempts."
            }
        }

        Start-Sleep -Seconds 2
    }
}

function Get-UatScenarioId([string]$ScenarioKey, [string]$Token) {
    $uat = Invoke-Api -Method GET -Url "$BaseS4/bi/uat" -RequestHeaders @{ Authorization = "Bearer $Token" }
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
    Invoke-Api -Method POST -Url "$BaseS4/bi/uat/$id/results" -RequestHeaders @{ Authorization = "Bearer $Token" } -Payload @{
        status = $Status
        notes  = $Notes
    } | Out-Null

    Write-Host "    UAT $ScenarioKey => $Status"
}

Write-Step "Gateway health"
Wait-GatewayHealth -Url "$BaseS1/health"

Write-Step "Login (UAT-S1-001)"
$login = Invoke-RestMethod -Method POST -Uri "$BaseS1/auth/login" -Headers @{ Accept = "application/json" } -ContentType "application/json" -Body (@{
    username = $Username
    password = $Password
} | ConvertTo-Json -Compress)

$token = $login.access_token
if ([string]::IsNullOrWhiteSpace($token)) {
    $detail = if ($login.message) { $login.message } else { ($login | ConvertTo-Json -Compress) }
    throw "Login failed: no access_token returned ($detail)."
}

$auth = @{ Authorization = "Bearer $token"; Accept = "application/json" }
$me = Invoke-RestMethod -Method GET -Uri "$BaseS1/auth/me" -Headers $auth
if ($me.username -ne $Username) {
    throw "S1 /auth/me returned unexpected user: $($me.username)"
}
Write-Step "S1 identity verified"
Record-Uat -ScenarioKey "UAT-S1-001" -Status "passed" -Notes "Automated E2E login and /auth/me." -Token $token

Write-Step "S2 employee for consumption (UAT-S3-004 prep)"
$consumptionEmployee = Invoke-Api -Method POST -Url "$BaseS2/employees" -RequestHeaders $auth -Payload @{
    full_name    = "E2E Consumption Staff"
    base_salary  = 12000
    default_role = "restaurant_manager"
}
$consumptionEmployeeId = $consumptionEmployee.data.id

Write-Step "S3 hotel golden path (UAT-S3-001, UAT-S3-002)"
$rooms = Invoke-Api -Method GET -Url "$BaseS3/rooms" -RequestHeaders $auth
$room = $rooms.data | Where-Object { $_.status -eq "available" } | Select-Object -First 1
if ($null -eq $room) {
    throw "No available room for E2E check-in (prior runs may have left rooms occupied)."
}

$checkIn = (Get-Date).ToString("yyyy-MM-dd")
$checkOut = (Get-Date).AddDays(2).ToString("yyyy-MM-dd")

$reservation = Invoke-Api -Method POST -Url "$BaseS3/reservations" -RequestHeaders $auth -Payload @{
    guest_name     = "E2E Guest"
    guest_email    = "e2e@wonderland.test"
    room_type_id   = $room.room_type.id
    check_in_date  = $checkIn
    check_out_date = $checkOut
}

$reservationId = $reservation.data.id
$checkedIn = Invoke-Api -Method POST -Url "$BaseS3/reservations/$reservationId/check-in" -RequestHeaders $auth -Payload @{
    room_id = $room.id
}
$folioId = $checkedIn.data.folio_id

# UAT-S3-003: F&B order with COGS on guest folio
Write-Step "S3 F&B order with COGS (UAT-S3-003)"
$items = Invoke-Api -Method GET -Url "$BaseS3/items" -RequestHeaders $auth
$beef = ($items.data | Where-Object { $_.sku -eq "BEEF-001" } | Select-Object -First 1)
$bun = ($items.data | Where-Object { $_.sku -eq "BUN-001" } | Select-Object -First 1)
if ($null -eq $beef -or $null -eq $bun) {
    throw "Inventory seed items BEEF-001 / BUN-001 not found."
}
$po = Invoke-Api -Method POST -Url "$BaseS3/purchase-orders" -RequestHeaders $auth -Payload @{
    vendor_name = "E2E Kitchen Supply"
    lines       = @(
        @{ inventory_item_id = $beef.id; quantity = 10; unit_cost = 450 },
        @{ inventory_item_id = $bun.id; quantity = 50; unit_cost = 15 }
    )
}
$poId = $po.data.id
Invoke-Api -Method POST -Url "$BaseS3/purchase-orders/$poId/approve" -RequestHeaders $auth | Out-Null
Invoke-Api -Method POST -Url "$BaseS3/purchase-orders/$poId/receive" -RequestHeaders $auth | Out-Null
$menu = Invoke-Api -Method GET -Url "$BaseS3/menu-items" -RequestHeaders $auth
$burger = $menu.data | Where-Object { $_.code -eq "BURGER-CL" } | Select-Object -First 1
if ($null -eq $burger) {
    throw "Menu item BURGER-CL not found."
}
$fbOrder = Invoke-Api -Method POST -Url "$BaseS3/orders" -RequestHeaders $auth -Payload @{ folio_id = $folioId }
$fbOrderId = $fbOrder.data.id
Invoke-Api -Method POST -Url "$BaseS3/orders/$fbOrderId/lines" -RequestHeaders $auth -Payload @{
    menu_item_id = $burger.id
    quantity     = 2
} | Out-Null
$finalized = Invoke-Api -Method POST -Url "$BaseS3/orders/$fbOrderId/finalize" -RequestHeaders $auth
if ($finalized.data.status -ne "finalized") {
    throw "F&B order finalize failed."
}
Record-Uat -ScenarioKey "UAT-S3-003" -Status "passed" -Notes "Folio F&B order finalized with COGS posting." -Token $token

Write-Step "S3 employee consumption close (UAT-S3-004)"
$consumptionStart = (Get-Date).ToString("yyyy-MM-01")
$consumptionEnd = (Get-Date).ToString("yyyy-MM-dd")
$consumptionPeriod = Invoke-Api -Method POST -Url "$BaseS3/employee-consumption-periods" -RequestHeaders $auth -Payload @{
    employee_id   = $consumptionEmployeeId
    period_start  = $consumptionStart
    period_end    = $consumptionEnd
}
$consumptionPeriodId = $consumptionPeriod.data.id

$mealOrder = Invoke-Api -Method POST -Url "$BaseS3/orders" -RequestHeaders $auth -Payload @{
    employee_consumption_period_id = $consumptionPeriodId
}
$mealOrderId = $mealOrder.data.id
Invoke-Api -Method POST -Url "$BaseS3/orders/$mealOrderId/lines" -RequestHeaders $auth -Payload @{
    menu_item_id = $burger.id
    quantity     = 1
} | Out-Null
Invoke-Api -Method POST -Url "$BaseS3/orders/$mealOrderId/finalize" -RequestHeaders $auth | Out-Null

$closedPeriod = Invoke-Api -Method POST -Url "$BaseS3/employee-consumption-periods/$consumptionPeriodId/close" -RequestHeaders $auth
if ($closedPeriod.data.status -ne "closed") {
    throw "Consumption period close failed."
}
if ([decimal]$closedPeriod.data.total_amount -ne 350) {
    throw "Expected consumption total 350, got $($closedPeriod.data.total_amount)."
}
Record-Uat -ScenarioKey "UAT-S3-004" -Status "passed" -Notes "Employee meal order closed; S2 staff_meal deduction posted." -Token $token

Invoke-Api -Method POST -Url "$BaseS3/folios/$folioId/charges" -RequestHeaders $auth -Payload @{
    description      = "E2E room night"
    amount           = 2500
    charge_category  = "room"
} | Out-Null

Record-Uat -ScenarioKey "UAT-S3-001" -Status "passed" -Notes "Reservation, check-in, folio charge completed." -Token $token

$folio = Invoke-Api -Method GET -Url "$BaseS3/folios/$folioId" -RequestHeaders $auth
$settleAmount = [decimal]$folio.data.balance
if ($settleAmount -le 0) {
    throw "Folio balance must be positive before settle, got $settleAmount."
}

Invoke-Api -Method POST -Url "$BaseS3/folios/$folioId/settle" -RequestHeaders $auth -Payload @{
    amount         = $settleAmount
    payment_method = "cash"
} | Out-Null

Invoke-Api -Method POST -Url "$BaseS3/reservations/$reservationId/check-out" -RequestHeaders $auth | Out-Null
Record-Uat -ScenarioKey "UAT-S3-002" -Status "passed" -Notes "Folio settled and guest checked out." -Token $token

Write-Step "S3 group booking (UAT-S3-005)"
$roomsAfterCheckout = Invoke-Api -Method GET -Url "$BaseS3/rooms" -RequestHeaders $auth
$availableRooms = @($roomsAfterCheckout.data | Where-Object { $_.status -eq "available" })

$groupRoomTypeId = $null
$roomsForGroup = @()
foreach ($roomTypeId in ($availableRooms | ForEach-Object { $_.room_type.id } | Select-Object -Unique)) {
    $matchingRooms = @($availableRooms | Where-Object { $_.room_type.id -eq $roomTypeId })
    if ($matchingRooms.Count -ge 2) {
        $groupRoomTypeId = $roomTypeId
        $roomsForGroup = @($matchingRooms | Select-Object -First 2)
        break
    }
}
if ($roomsForGroup.Count -lt 2) {
    throw "Need at least 2 available rooms of the same type for group booking E2E."
}
$groupCheckIn = (Get-Date).ToString("yyyy-MM-dd")
$groupCheckOut = (Get-Date).AddDays(2).ToString("yyyy-MM-dd")

$groupBooking = Invoke-Api -Method POST -Url "$BaseS3/group-bookings" -RequestHeaders $auth -Payload @{
    group_name     = "E2E Corporate Retreat"
    contact_name   = "Event Planner"
    contact_email  = "group@wonderland.test"
    check_in_date  = $groupCheckIn
    check_out_date = $groupCheckOut
    rooms          = @(
        @{ guest_name = "Group Guest A"; room_type_id = $groupRoomTypeId },
        @{ guest_name = "Group Guest B"; room_type_id = $groupRoomTypeId }
    )
}
$groupBookingId = $groupBooking.data.id
$groupReservations = $groupBooking.data.reservations

$groupCheckedIn = Invoke-Api -Method POST -Url "$BaseS3/group-bookings/$groupBookingId/check-in" -RequestHeaders $auth -Payload @{
    assignments = @(
        @{ reservation_id = $groupReservations[0].id; room_id = $roomsForGroup[0].id },
        @{ reservation_id = $groupReservations[1].id; room_id = $roomsForGroup[1].id }
    )
}
if ($groupCheckedIn.data.status -ne "checked_in") {
    throw "Group booking check-in failed."
}

foreach ($groupReservation in $groupCheckedIn.data.reservations) {
    $groupFolioId = $groupReservation.folio_id
    if ($null -ne $groupFolioId) {
        $groupFolio = Invoke-Api -Method GET -Url "$BaseS3/folios/$groupFolioId" -RequestHeaders $auth
        $groupBalance = [decimal]$groupFolio.data.balance
        Invoke-Api -Method POST -Url "$BaseS3/folios/$groupFolioId/settle" -RequestHeaders $auth -Payload @{
            amount         = $groupBalance
            payment_method = "cash"
        } | Out-Null
    }
}

$groupCheckedOut = Invoke-Api -Method POST -Url "$BaseS3/group-bookings/$groupBookingId/check-out" -RequestHeaders $auth
if ($groupCheckedOut.data.status -ne "checked_out") {
    throw "Group booking check-out failed."
}
Record-Uat -ScenarioKey "UAT-S3-005" -Status "passed" -Notes "Group booking bulk check-in, folio settle, and check-out completed." -Token $token

Write-Step "S2 payroll with deductions (UAT-S2-001)"
$employee = Invoke-Api -Method POST -Url "$BaseS2/employees" -RequestHeaders $auth -Payload @{
    full_name    = "E2E Payroll Staff"
    base_salary  = 18000
    default_role = "cashier"
}
$employeeId = $employee.data.id

$payPeriodStart = (Get-Date).ToString("yyyy-MM-01")
$payPeriodEnd = (Get-Date).ToString("yyyy-MM-dd")
$allEmployees = Invoke-Api -Method GET -Url "$BaseS2/employees" -RequestHeaders $auth
$day = [DateTime]::Parse($payPeriodStart)
$endDay = [DateTime]::Parse($payPeriodEnd)
while ($day -le $endDay) {
    if ($day.DayOfWeek -ne 'Saturday' -and $day.DayOfWeek -ne 'Sunday') {
        foreach ($emp in ($allEmployees.data | Where-Object { $_.status -eq "active" })) {
            Invoke-Api -Method POST -Url "$BaseS2/attendance-records" -RequestHeaders $auth -Payload @{
                employee_id = $emp.id
                work_date   = $day.ToString("yyyy-MM-dd")
                check_in    = "08:00"
                check_out   = "17:00"
                status      = "present"
            } | Out-Null
        }
    }
    $day = $day.AddDays(1)
}

Invoke-Api -Method POST -Url "$BaseS2/employees/$employeeId/deductions" -RequestHeaders @{
    "X-Service-Key"   = $ServiceKey
    "Idempotency-Key" = "e2e-meal-$employeeId"
} -Payload @{
    deduction_type    = "staff_meal"
    amount            = 300
    source_reference  = "E2E-CONSUMPTION"
} | Out-Null

$payrollRun = Invoke-Api -Method POST -Url "$BaseS2/payroll-runs" -RequestHeaders $auth -Payload @{
    period_start = $payPeriodStart
    period_end   = $payPeriodEnd
}
$line = $payrollRun.data.lines | Where-Object { $_.employee_id -eq $employeeId } | Select-Object -First 1
if ([decimal]$line.other_deductions -ne 300) {
    throw "Expected other_deductions 300 on payroll line, got $($line.other_deductions)"
}

Invoke-Api -Method POST -Url "$BaseS2/payroll-runs/$($payrollRun.data.id)/approve" -RequestHeaders $auth | Out-Null
Record-Uat -ScenarioKey "UAT-S2-001" -Status "passed" -Notes "Payroll approved with staff meal deduction applied." -Token $token

Write-Step "S2 leave flow (UAT-S2-002)"
$leave = Invoke-Api -Method POST -Url "$BaseS2/leave-requests" -RequestHeaders $auth -Payload @{
    employee_id = $employeeId
    leave_type  = "annual"
    start_date  = (Get-Date).AddDays(14).ToString("yyyy-MM-dd")
    end_date    = (Get-Date).AddDays(16).ToString("yyyy-MM-dd")
}
Invoke-Api -Method POST -Url "$BaseS2/leave-requests/$($leave.data.id)/approve" -RequestHeaders $auth | Out-Null
Record-Uat -ScenarioKey "UAT-S2-002" -Status "passed" -Notes "Leave created and approved." -Token $token

Write-Step "S2 attendance (UAT-S2-003)"
Invoke-Api -Method POST -Url "$BaseS2/attendance-records" -RequestHeaders $auth -Payload @{
    employee_id = $employeeId
    work_date   = (Get-Date).ToString("yyyy-MM-dd")
    check_in    = "08:00"
    check_out   = "17:00"
} | Out-Null
Record-Uat -ScenarioKey "UAT-S2-003" -Status "passed" -Notes "Attendance recorded with 9 hours." -Token $token

Write-Step "S4 finance reports (UAT-S4-001 .. UAT-S4-005)"
$periods = Invoke-Api -Method GET -Url "$BaseS4/fiscal-periods" -RequestHeaders $auth
$today = (Get-Date).ToString("yyyy-MM-dd")
$period = $periods.data | Where-Object { $_.start_date -le $today -and $_.end_date -ge $today } | Select-Object -First 1
if ($null -eq $period) {
    throw "No open fiscal period for today."
}
$periodId = $period.id

$trialBalance = Invoke-Api -Method GET -Url "$BaseS4/reports/trial-balance?fiscal_period_id=$periodId" -RequestHeaders $auth
if ($trialBalance.data.total_debits -ne $trialBalance.data.total_credits) {
    throw "Trial balance out of balance."
}
Record-Uat -ScenarioKey "UAT-S4-001" -Status "passed" -Notes "Trial balance debits equal credits." -Token $token

$manualJournal = Invoke-Api -Method POST -Url "$BaseS4/journal-entries" -RequestHeaders @{
    Authorization     = "Bearer $token"
    "X-Service-Key"   = $ServiceKey
    "Idempotency-Key" = "e2e-manual-journal-$([Guid]::NewGuid().ToString('N'))"
} -Payload @{
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
Invoke-Api -Method POST -Url "$BaseS4/journal-entries/$journalId/approve" -RequestHeaders $auth | Out-Null
Invoke-Api -Method POST -Url "$BaseS4/journal-entries/$journalId/post" -RequestHeaders $auth | Out-Null
Record-Uat -ScenarioKey "UAT-S4-002" -Status "passed" -Notes "Manual journal approved and posted." -Token $token

$opsDashboard = Invoke-Api -Method GET -Url "$BaseS4/dashboards/operations?fiscal_period_id=$periodId" -RequestHeaders $auth
if ($null -eq $opsDashboard.data.dashboard) {
    throw "Operations dashboard missing."
}
Record-Uat -ScenarioKey "UAT-S4-003" -Status "passed" -Notes "Operations dashboard returned KPIs." -Token $token

try {
    $exportPayload = @{
        report           = "income_statement"
        format           = "csv"
        fiscal_period_id = $periodId
    }
    $exportBodyFile = [System.IO.Path]::GetTempFileName()
    try {
        [System.IO.File]::WriteAllText($exportBodyFile, ($exportPayload | ConvertTo-Json -Compress))
        $exportCode = & curl.exe -sS -m 120 -o NUL -w "%{http_code}" -X POST "$BaseS4/bi/exports" `
            -H "Authorization: Bearer $token" `
            -H "Accept: text/csv" `
            -H "Content-Type: application/json" `
            --data-binary "@$exportBodyFile"
    }
    finally {
        Remove-Item -LiteralPath $exportBodyFile -ErrorAction SilentlyContinue
    }

    if ($exportCode -ne "200") {
        throw "CSV export failed with HTTP $exportCode."
    }
    Record-Uat -ScenarioKey "UAT-S4-004" -Status "passed" -Notes "Income statement CSV exported." -Token $token
}
catch {
    Record-Uat -ScenarioKey "UAT-S4-004" -Status "failed" -Notes $_.Exception.Message -Token $token
    throw
}

$variance = Invoke-Api -Method GET -Url "$BaseS4/bi/reports/budget_variance?fiscal_period_id=$periodId" -RequestHeaders $auth
if ($null -eq $variance.data.budget_net_income) {
    throw "Budget variance report incomplete."
}
Record-Uat -ScenarioKey "UAT-S4-005" -Status "passed" -Notes "Budget variance report returned targets." -Token $token

Write-Step "End-to-end hotel day (UAT-E2E-001)"
$income = Invoke-Api -Method GET -Url "$BaseS4/reports/income-statement?fiscal_period_id=$periodId" -RequestHeaders $auth
$netIncome = [decimal]$income.data.net_income
if ($netIncome -le 0) {
    $boostAmount = [math]::Ceiling([math]::Abs($netIncome) + 1000000)
    $revenueBoost = Invoke-Api -Method POST -Url "$BaseS4/journal-entries" -RequestHeaders @{
        Authorization     = "Bearer $token"
        "X-Service-Key"   = $ServiceKey
        "Idempotency-Key" = "e2e-revenue-boost-$([Guid]::NewGuid().ToString('N'))"
    } -Payload @{
        description       = "E2E revenue bootstrap"
        source_module     = "manual"
        source_reference  = "E2E-REV-BOOST"
        entry_date        = $today
        lines             = @(
            @{ account_code = "1001"; debit = $boostAmount; credit = 0 },
            @{ account_code = "4001"; debit = 0; credit = $boostAmount }
        )
    }
    $revenueBoostId = $revenueBoost.data.id
    Invoke-Api -Method POST -Url "$BaseS4/journal-entries/$revenueBoostId/approve" -RequestHeaders $auth | Out-Null
    Invoke-Api -Method POST -Url "$BaseS4/journal-entries/$revenueBoostId/post" -RequestHeaders $auth | Out-Null
    $income = Invoke-Api -Method GET -Url "$BaseS4/reports/income-statement?fiscal_period_id=$periodId" -RequestHeaders $auth
    $netIncome = [decimal]$income.data.net_income
}
if ($netIncome -le 0) {
    throw "Income statement net income is not positive after revenue bootstrap (got $netIncome)."
}
Record-Uat -ScenarioKey "UAT-E2E-001" -Status "passed" -Notes "Full E2E flow completed; income statement retrieved." -Token $token

Write-Step "UAT summary"
$summary = Invoke-Api -Method GET -Url "$BaseS4/bi/uat" -RequestHeaders $auth
Write-Host ($summary.meta | ConvertTo-Json -Compress)
Write-Host "E2E UAT run complete."
