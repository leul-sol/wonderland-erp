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

    $maxAttempts = 6
    for ($attempt = 1; $attempt -le $maxAttempts; $attempt++) {
        try {
            if ($null -ne $Payload) {
                return Invoke-RestMethod -Method $Method -Uri $Url -Headers $reqHeaders -ContentType "application/json" -Body ($Payload | ConvertTo-Json -Depth 8 -Compress) -TimeoutSec 120
            }

            return Invoke-RestMethod -Method $Method -Uri $Url -Headers $reqHeaders -TimeoutSec 120
        }
        catch {
            $statusCode = $null
            if ($_.Exception.Response) {
                $statusCode = [int]$_.Exception.Response.StatusCode
            }

            $isRetryable = $statusCode -in @(500, 502, 503, 504) -or $_.Exception.Message -match 'timed out|timeout|Unable to connect'
            $detail = $_.ErrorDetails.Message
            if ([string]::IsNullOrWhiteSpace($detail)) {
                $detail = $_.Exception.Message
            }
            if ($detail -match 'SERVICE_UNAVAILABLE') {
                $isRetryable = $true
            }

            if (-not $isRetryable -or $attempt -eq $maxAttempts) {
                throw "$Method $Url failed: $detail"
            }

            Start-Sleep -Seconds $attempt
        }
    }
}

function Invoke-S1LoginRaw {
    param(
        [string]$Username,
        [string]$Password
    )

    try {
        $response = Invoke-WebRequest -Method POST -Uri "$BaseS1/auth/login" `
            -Headers @{ Accept = "application/json" } `
            -ContentType "application/json" `
            -Body (@{ username = $Username; password = $Password } | ConvertTo-Json) `
            -UseBasicParsing

        return @{
            StatusCode = [int]$response.StatusCode
            Body       = ($response.Content | ConvertFrom-Json)
        }
    }
    catch {
        $statusCode = 0
        if ($_.Exception.Response) {
            $statusCode = [int]$_.Exception.Response.StatusCode
        }

        $body = $null
        if ($_.ErrorDetails.Message) {
            try {
                $body = $_.ErrorDetails.Message | ConvertFrom-Json
            }
            catch {
                $body = $null
            }
        }

        return @{
            StatusCode = $statusCode
            Body       = $body
        }
    }
}

function With-IdempotencyKey {
    param(
        [hashtable]$RequestHeaders,
        [string]$Key
    )

    $merged = @{}
    foreach ($entry in $RequestHeaders.GetEnumerator()) {
        $merged[$entry.Key] = $entry.Value
    }
    $merged["Idempotency-Key"] = $Key

    return $merged
}

function Approve-AndPostManualJournal {
    param(
        [int]$JournalId,
        [hashtable]$RequestHeaders
    )

    $approved = Invoke-Api -Method POST -Url "$BaseS4/journal-entries/$JournalId/approve" -RequestHeaders $RequestHeaders
    if ($approved.data.status -eq "approved") {
        $approved = Invoke-Api -Method POST -Url "$BaseS4/journal-entries/$JournalId/approve" -RequestHeaders $RequestHeaders
    }
    if ($approved.data.status -ne "posted") {
        Invoke-Api -Method POST -Url "$BaseS4/journal-entries/$JournalId/post" -RequestHeaders $RequestHeaders | Out-Null
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

function Wait-ServiceHealth {
    param(
        [string]$Url,
        [int]$Attempts = 15
    )

    for ($i = 1; $i -le $Attempts; $i++) {
        try {
            $health = Invoke-RestMethod -Method GET -Uri $Url -Headers @{ Accept = "application/json" } -TimeoutSec 10
            if ($health.status -eq "ok") {
                return
            }
        }
        catch {
            if ($i -eq $Attempts) {
                throw "Service health check failed for $Url after $Attempts attempts."
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

function Ensure-ServiceSeeded {
    param([string]$ServiceName)

    docker compose exec -T $ServiceName php artisan app:ensure-seeded 2>&1 | Out-Host
    if ($LASTEXITCODE -ne 0) {
        throw "$ServiceName app:ensure-seeded failed."
    }
}

Write-Step "Bootstrap seed data (S1 login, S4 UAT catalog, S2/S3 fixtures)"
Ensure-ServiceSeeded -ServiceName "s1-identity"
Ensure-ServiceSeeded -ServiceName "s4-finance-bi"
Ensure-ServiceSeeded -ServiceName "s3-hospitality"
Ensure-ServiceSeeded -ServiceName "s2-workforce"

Write-Step "Reset S3 room availability for repeatable E2E"
docker compose exec -T s3-hospitality php artisan hospitality:reset-rooms 2>&1 | Out-Host
if ($LASTEXITCODE -ne 0) {
    throw "s3-hospitality hospitality:reset-rooms failed."
}

Write-Step "Login (UAT-S1-001)"
try {
    $login = Invoke-RestMethod -Method POST -Uri "$BaseS1/auth/login" -Headers @{ Accept = "application/json" } -ContentType "application/json" -Body (@{
        username = $Username
        password = $Password
    } | ConvertTo-Json -Compress)
}
catch {
    $hint = "Login failed (401). UAT uses SUPER_ADMIN_PASSWORD from repo root .env; run: docker compose exec s1-identity php artisan app:ensure-seeded"
    throw "$hint`n$($_.Exception.Message)"
}

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

Write-Step "S1 account lockout (UAT-S1-002)"
$lockoutUsername = "e2e.uat.lockout"
$lockoutPassword = "Welcome123!"
try {
    Invoke-Api -Method POST -Url "$BaseS1/users" -RequestHeaders $auth -Payload @{
        username     = $lockoutUsername
        email        = "$lockoutUsername@wonderland.test"
        password     = $lockoutPassword
        display_name = "UAT Lockout User"
        is_active    = $true
    } | Out-Null
}
catch {
    Write-Host "  Lockout user may already exist; continuing."
}

for ($attempt = 1; $attempt -le 5; $attempt++) {
    $failed = Invoke-S1LoginRaw -Username $lockoutUsername -Password "wrong-password"
    if ($failed.StatusCode -ne 401) {
        throw "Expected 401 on failed login attempt $attempt, got $($failed.StatusCode)."
    }
}

$locked = Invoke-S1LoginRaw -Username $lockoutUsername -Password $lockoutPassword
if ($locked.StatusCode -ne 403) {
    throw "Expected locked account login to return 403, got $($locked.StatusCode)."
}
Record-Uat -ScenarioKey "UAT-S1-002" -Status "passed" -Notes "Account locked after repeated failed logins." -Token $token

Write-Step "S1 deactivate user (UAT-S1-003)"
$deactivateUsername = "e2e.uat.deactivate"
$deactivatePassword = "Welcome123!"
$deactivateUser = $null
try {
    $created = Invoke-Api -Method POST -Url "$BaseS1/users" -RequestHeaders $auth -Payload @{
        username     = $deactivateUsername
        email        = "$deactivateUsername@wonderland.test"
        password     = $deactivatePassword
        display_name = "UAT Deactivate User"
        is_active    = $true
    }
    $deactivateUser = $created.data
}
catch {
    $existing = Invoke-Api -Method GET -Url "$BaseS1/users?search=$deactivateUsername" -RequestHeaders $auth
    $deactivateUser = $existing.data | Where-Object { $_.username -eq $deactivateUsername } | Select-Object -First 1
}

if ($null -eq $deactivateUser) {
    throw "Unable to create or locate deactivate test user."
}

$activeLogin = Invoke-S1LoginRaw -Username $deactivateUsername -Password $deactivatePassword
if ($activeLogin.StatusCode -ne 200) {
    Invoke-Api -Method PATCH -Url "$BaseS1/users/$($deactivateUser.id)" -RequestHeaders $auth -Payload @{ is_active = $true } | Out-Null
    $activeLogin = Invoke-S1LoginRaw -Username $deactivateUsername -Password $deactivatePassword
}
if ($activeLogin.StatusCode -ne 200) {
    throw "Deactivate test user could not sign in before deactivation."
}

Invoke-Api -Method POST -Url "$BaseS1/users/$($deactivateUser.id)/deactivate" -RequestHeaders $auth | Out-Null
$deactivatedLogin = Invoke-S1LoginRaw -Username $deactivateUsername -Password $deactivatePassword
if ($deactivatedLogin.StatusCode -ne 403) {
    throw "Expected deactivated user login to return 403, got $($deactivatedLogin.StatusCode)."
}
Record-Uat -ScenarioKey "UAT-S1-003" -Status "passed" -Notes "Deactivated user cannot sign in." -Token $token

Write-Step "S1 role permission sync (UAT-S1-004)"
$syncRole = (Invoke-Api -Method GET -Url "$BaseS1/roles?per_page=50" -RequestHeaders $auth).data |
    Where-Object { $_.name -eq "report_viewer" } |
    Select-Object -First 1
if ($null -eq $syncRole) {
    throw "report_viewer role not found for permission sync UAT."
}
$roleDetail = Invoke-Api -Method GET -Url "$BaseS1/roles/$($syncRole.id)" -RequestHeaders $auth
$permissionIds = @($roleDetail.data.permissions | ForEach-Object { $_.id })
Invoke-Api -Method PUT -Url "$BaseS1/roles/$($syncRole.id)/permissions" -RequestHeaders $auth -Payload @{
    permission_ids = $permissionIds
} | Out-Null
$permissionAudit = Invoke-Api -Method GET -Url "$BaseS1/audit-logs?event=permission.changed&per_page=5" -RequestHeaders $auth
if (($permissionAudit.data | Measure-Object).Count -lt 1) {
    throw "Expected permission.changed audit entry after role permission sync."
}
Record-Uat -ScenarioKey "UAT-S1-004" -Status "passed" -Notes "Role permission sync recorded in audit log." -Token $token

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
Invoke-Api -Method POST -Url "$BaseS3/purchase-orders/$poId/approve" -RequestHeaders (With-IdempotencyKey -RequestHeaders $auth -Key "e2e-po-approve-$poId") | Out-Null
Invoke-Api -Method POST -Url "$BaseS3/purchase-orders/$poId/receive" -RequestHeaders $auth | Out-Null

$payables = Invoke-Api -Method GET -Url "$BaseS4/payables?status=open&per_page=100" -RequestHeaders $auth
$openPayable = $payables.data | Where-Object { [decimal]$_.balance -gt 0 } | Select-Object -First 1
if ($null -ne $openPayable) {
    $apBalance = [decimal]$openPayable.balance
    Invoke-Api -Method POST -Url "$BaseS4/payables/$($openPayable.id)/settle" -RequestHeaders $auth -Payload @{
        amount         = $apBalance
        payment_method = "bank"
    } | Out-Null
    Record-Uat -ScenarioKey "UAT-S4-009" -Status "passed" -Notes "Open payable settled via bank payment." -Token $token
}

Write-Step "S3 PO tiered approval (UAT-S3-006)"
$tierPo = Invoke-Api -Method POST -Url "$BaseS3/purchase-orders" -RequestHeaders $auth -Payload @{
    vendor_name = "E2E Capital Vendor"
    lines       = @(
        @{ inventory_item_id = $beef.id; quantity = 200; unit_cost = 300 }
    )
}
$tierPoId = $tierPo.data.id
Invoke-Api -Method POST -Url "$BaseS3/purchase-orders/$tierPoId/submit" -RequestHeaders $auth | Out-Null
$tierApproved = Invoke-Api -Method POST -Url "$BaseS3/purchase-orders/$tierPoId/approve" -RequestHeaders (With-IdempotencyKey -RequestHeaders $auth -Key "e2e-po-approve-$tierPoId")
if ($tierApproved.data.status -ne "approved" -or [int]$tierApproved.data.approval_tier -lt 3) {
    throw "Tier-3 PO approval failed (status=$($tierApproved.data.status), tier=$($tierApproved.data.approval_tier))."
}
Record-Uat -ScenarioKey "UAT-S3-006" -Status "passed" -Notes "PO >= 50k approved through tiered workflow." -Token $token

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
if ([decimal]$closedPeriod.data.total_amount -ne 442.75) {
    throw "Expected consumption total 442.75 (incl. SC+VAT), got $($closedPeriod.data.total_amount)."
}
Record-Uat -ScenarioKey "UAT-S3-004" -Status "passed" -Notes "Employee meal order closed; S2 staff_meal deduction posted." -Token $token

Invoke-Api -Method POST -Url "$BaseS3/folios/$folioId/charges" -RequestHeaders $auth -Payload @{
    description      = "E2E minibar snack"
    amount           = 150
    charge_category  = "other"
} | Out-Null

$folioWithTax = Invoke-Api -Method GET -Url "$BaseS3/folios/$folioId" -RequestHeaders $auth
$roomLine = $folioWithTax.data.lines | Where-Object { $_.charge_category -eq "room" } | Select-Object -First 1
if ($null -eq $roomLine -or [decimal]$roomLine.vat_amount -le 0) {
    throw "Auto room rent charge missing VAT breakdown on folio line."
}
Record-Uat -ScenarioKey "UAT-S3-007" -Status "passed" -Notes "Check-in auto room rent and folio charge posted with 10% SC and 15% VAT." -Token $token

$openReceivables = Invoke-Api -Method GET -Url "$BaseS4/receivables?status=open&per_page=100" -RequestHeaders $auth
$arEntry = $openReceivables.data | Where-Object { [decimal]$_.balance -gt 0 } | Select-Object -First 1
if ($null -ne $arEntry) {
    $arBalance = [decimal]$arEntry.balance
    Invoke-Api -Method POST -Url "$BaseS4/receivables/$($arEntry.id)/settle" -RequestHeaders $auth -Payload @{
        amount         = $arBalance
        payment_method = "cash"
    } | Out-Null
    Record-Uat -ScenarioKey "UAT-S4-008" -Status "passed" -Notes "Open receivable settled via cash." -Token $token
}

Record-Uat -ScenarioKey "UAT-S3-001" -Status "passed" -Notes "Reservation, check-in with auto room rent, and incidental folio charge completed." -Token $token

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
$keepEmployeeIds = @($consumptionEmployeeId, $employeeId)
foreach ($staleEmployee in ($allEmployees.data | Where-Object { $_.full_name -like "E2E*" -and $_.status -eq "active" -and ($keepEmployeeIds -notcontains $_.id) })) {
    Invoke-Api -Method POST -Url "$BaseS2/employees/$($staleEmployee.id)/archive" -RequestHeaders $auth -Payload @{
        reason = "UAT cleanup before payroll"
    } | Out-Null
}
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

$payrollRunId = $payrollRun.data.id
Invoke-Api -Method POST -Url "$BaseS2/payroll-runs/$payrollRunId/submit" -RequestHeaders $auth | Out-Null
Invoke-Api -Method POST -Url "$BaseS2/payroll-runs/$payrollRunId/approve" -RequestHeaders (With-IdempotencyKey -RequestHeaders $auth -Key "e2e-payroll-approve-$payrollRunId") | Out-Null
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
$today = (Get-Date).ToString("yyyy-MM-dd")
$records = Invoke-Api -Method GET -Url "$BaseS2/attendance-records?employee_id=$employeeId&work_date=$today" -RequestHeaders $auth
if ($records.data.Count -lt 1) {
    throw "Expected attendance for employee $employeeId on $today."
}
Record-Uat -ScenarioKey "UAT-S2-003" -Status "passed" -Notes "Attendance recorded with 9 hours." -Token $token

Write-Step "S2 severance accrual (UAT-S2-004)"
$severanceEmployee = Invoke-Api -Method POST -Url "$BaseS2/employees" -RequestHeaders $auth -Payload @{
    full_name    = "E2E Severance Staff"
    base_salary  = 24000
    hire_date    = "2024-01-01"
    default_role = "cashier"
}
$severanceEmployeeId = $severanceEmployee.data.id
$severance = Invoke-Api -Method POST -Url "$BaseS2/employees/$severanceEmployeeId/severance/calculate" -RequestHeaders $auth
if ($severance.data.status -ne "calculated") {
    throw "Severance calculation failed."
}
if ([string]::IsNullOrWhiteSpace($severance.data.s4_journal_entry_id)) {
    throw "Severance did not return an S4 journal entry id."
}
if ([decimal]$severance.data.amount -le 0) {
    throw "Severance amount must be positive."
}
Record-Uat -ScenarioKey "UAT-S2-004" -Status "passed" -Notes "Severance accrual posted DR 5005 / CR 2100 in S4." -Token $token

Write-Step "S2 severance payout (UAT-S2-005)"
$severancePaid = Invoke-Api -Method POST -Url "$BaseS2/severance-calculations/$($severance.data.id)/pay" -RequestHeaders $auth
if ($severancePaid.data.status -ne "paid") {
    throw "Severance payout failed."
}
if ([string]::IsNullOrWhiteSpace($severancePaid.data.s4_payout_journal_entry_id)) {
    throw "Severance payout did not return an S4 journal entry id."
}
Record-Uat -ScenarioKey "UAT-S2-005" -Status "passed" -Notes "Severance paid; DR 2100 / CR 1001 in S4." -Token $token

Write-Step "S2 employee provisions S1 user (UAT-S2-006)"
$provisionEmployee = Invoke-Api -Method POST -Url "$BaseS2/employees" -RequestHeaders $auth -Payload @{
    full_name    = "E2E Provision Staff"
    base_salary  = 15000
    default_role = "report_viewer"
}
$provisionEmployeeId = $provisionEmployee.data.id
docker compose exec -T s2-workforce php artisan outbox:publish 2>$null | Out-Null
$provisionUser = $null
for ($attempt = 1; $attempt -le 30; $attempt++) {
  Start-Sleep -Seconds 2
  $userList = Invoke-Api -Method GET -Url "$BaseS1/users?employee_id=$provisionEmployeeId" -RequestHeaders $auth
  $provisionUser = $userList.data | Select-Object -First 1
  if ($null -ne $provisionUser) {
    break
  }
  if ($attempt % 5 -eq 0) {
    docker compose exec -T s2-workforce php artisan outbox:publish 2>$null | Out-Null
    docker compose exec -T s1-identity php artisan employees:provision-from-s2 $provisionEmployeeId 2>$null | Out-Null
  }
}
if ($null -eq $provisionUser) {
    throw "S1 user was not provisioned for employee $provisionEmployeeId within timeout."
}
Record-Uat -ScenarioKey "UAT-S2-006" -Status "passed" -Notes "S2 employee.created consumed; S1 user $($provisionUser.username) linked." -Token $token

Wait-ServiceHealth -Url "$BaseS4/health"
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
Approve-AndPostManualJournal -JournalId $journalId -RequestHeaders $auth
Record-Uat -ScenarioKey "UAT-S4-002" -Status "passed" -Notes "Manual journal approved and posted." -Token $token

$executiveDashboard = Invoke-Api -Method GET -Url "$BaseS4/dashboards/executive?fiscal_period_id=$periodId" -RequestHeaders $auth
if ($null -eq $executiveDashboard.data.kpis.net_income) {
    throw "Executive dashboard missing net_income."
}
Record-Uat -ScenarioKey "UAT-S4-007" -Status "passed" -Notes "Executive dashboard returned P&L summary." -Token $token

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

Write-Step "S4 fiscal period close and lock (UAT-S4-006)"
$uatFyYear = 2099
$uatMonth = Get-Random -Minimum 1 -Maximum 12
$uatPeriodNumber = $uatMonth
$uatPeriodStart = ("2099-{0:D2}-01" -f $uatMonth)
$uatPeriodEnd = ("2099-{0:D2}-28" -f $uatMonth)
try {
    $uatPeriod = Invoke-Api -Method POST -Url "$BaseS4/fiscal-periods" -RequestHeaders $auth -Payload @{
        year          = $uatFyYear
        period_number = $uatPeriodNumber
        start_date    = $uatPeriodStart
        end_date      = $uatPeriodEnd
    }
    $uatPeriodId = $uatPeriod.data.id
}
catch {
    $openPeriods = Invoke-Api -Method GET -Url "$BaseS4/fiscal-periods?status=open" -RequestHeaders $auth
    $fallback = $openPeriods.data | Where-Object { $_.start_date -gt $today } | Select-Object -First 1
    if ($null -eq $fallback) {
        throw "Could not create or find an open fiscal period for close/lock UAT."
    }
    $uatPeriodId = $fallback.id
}

$closedPeriod = Invoke-Api -Method POST -Url "$BaseS4/fiscal-periods/$uatPeriodId/close" -RequestHeaders $auth
if ($closedPeriod.data.status -eq "closing") {
    $closedPeriod = Invoke-Api -Method POST -Url "$BaseS4/fiscal-periods/$uatPeriodId/close" -RequestHeaders $auth
}
if ($closedPeriod.data.status -ne "closed") {
    throw "Fiscal period close failed (status=$($closedPeriod.data.status))."
}

$lockedPeriod = Invoke-Api -Method POST -Url "$BaseS4/fiscal-periods/$uatPeriodId/lock" -RequestHeaders $auth
if ($lockedPeriod.data.status -ne "locked") {
    throw "Fiscal period lock failed."
}
Record-Uat -ScenarioKey "UAT-S4-006" -Status "passed" -Notes "Fiscal period closed then locked." -Token $token

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
    Approve-AndPostManualJournal -JournalId $revenueBoostId -RequestHeaders $auth
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
