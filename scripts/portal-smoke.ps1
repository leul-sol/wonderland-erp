# Portal UI smoke — front desk golden path through web-portal BFF (not direct S3 API).
# Resets room availability first so check-in can assign a room.
# Exit 0 when check-in → charge → settle → check-out succeeds.

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot

function Write-Step([string]$Message) {
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Get-EnvValue([string]$Name, [string]$Default) {
    $envPath = Join-Path $RepoRoot ".env"
    if (-not (Test-Path $envPath)) {
        return $Default
    }

    foreach ($line in Get-Content $envPath) {
        if ($line -match "^\s*$Name=(.+)$") {
            return $Matches[1].Trim()
        }
    }

    return $Default
}

function Get-XsrfFromJar([string]$CookieJar) {
    foreach ($line in Get-Content $CookieJar) {
        if ($line -match "^\s*#" -or $line.Trim() -eq "") { continue }
        $parts = $line -split "\t"
        if ($parts.Count -ge 7 -and $parts[5] -eq "XSRF-TOKEN") {
            return [uri]::UnescapeDataString($parts[6])
        }
    }

    throw "XSRF-TOKEN not found in cookie jar"
}

function Invoke-PortalSession([string]$BaseUrl, [string]$Username, [string]$Password) {
    $jar = New-TemporaryFile
    $sink = Join-Path $env:TEMP "portal-smoke-sink.txt"
    try {
        curl.exe -s -c $jar.FullName -b $jar.FullName "$BaseUrl/login" -o $sink | Out-Null
        $xsrf = Get-XsrfFromJar $jar.FullName
        curl.exe -s -c $jar.FullName -b $jar.FullName `
            -X POST "$BaseUrl/login" `
            -H "X-XSRF-TOKEN: $xsrf" `
            -H "Accept: text/html" `
            --data-urlencode "username=$Username" `
            --data-urlencode "password=$Password" `
            -o $sink -w "%{http_code}" | Out-Null
        return $jar.FullName
    } catch {
        Remove-Item $jar.FullName -Force -ErrorAction SilentlyContinue
        throw
    } finally {
        Remove-Item $sink -Force -ErrorAction SilentlyContinue
    }
}

function Invoke-PortalPost([string]$BaseUrl, [string]$CookieJar, [string]$Path, [hashtable]$Fields) {
    $xsrf = Get-XsrfFromJar $CookieJar
    $sink = Join-Path $env:TEMP "portal-smoke-post.txt"
    $headerFile = Join-Path $env:TEMP "portal-smoke-headers.txt"
    $args = @(
        "-s", "-c", $CookieJar, "-b", $CookieJar,
        "-X", "POST",
        "-H", "X-XSRF-TOKEN: $xsrf",
        "-H", "Accept: text/html",
        "-D", $headerFile,
        "-o", $sink,
        "$BaseUrl$Path"
    )
    foreach ($key in $Fields.Keys) {
        $args += "--data-urlencode"
        $args += "$key=$($Fields[$key])"
    }

    curl.exe @args | Out-Null
    $output = Get-Content $headerFile -ErrorAction SilentlyContinue
    Remove-Item $sink, $headerFile -Force -ErrorAction SilentlyContinue
    $statusLine = ($output | Select-String -Pattern "^HTTP/" | Select-Object -Last 1).Line
    $location = ($output | Select-String -Pattern "^Location:" | Select-Object -Last 1).Line

    if ($statusLine -notmatch "HTTP/\S+\s+(\d+)") {
        throw "Unexpected portal response for POST $Path"
    }

    return @{
        Status   = [int]$Matches[1]
        Location = if ($location) { ($location -replace "^Location:\s*", "").Trim() } else { "" }
    }
}

$GatewayHost = if ($env:GATEWAY_HOST) { $env:GATEWAY_HOST } else { "127.0.0.1" }
$PortalUrl = "http://$GatewayHost"
$ApiS1 = "http://$GatewayHost/s1/api/v1"
$ApiS3 = "http://$GatewayHost/s3/api/v1"
$Username = "super.admin"
$Password = Get-EnvValue "SUPER_ADMIN_PASSWORD" "AdminWonder@1234Land"

Write-Step "Reset S3 room availability"
docker compose exec -T s3-hospitality php artisan hospitality:reset-rooms 2>&1 | Out-Host

Write-Step "Resolve available room via S3 API"
$auth = Invoke-RestMethod -Method POST -Uri "$ApiS1/auth/login" -ContentType "application/json" `
    -Body (@{ username = $Username; password = $Password } | ConvertTo-Json)
$apiHeaders = @{ Authorization = "Bearer $($auth.access_token)" }
$room = (Invoke-RestMethod -Uri "$ApiS3/rooms?status=available" -Headers $apiHeaders).data | Select-Object -First 1
if ($null -eq $room) {
    throw "No available room for portal smoke."
}
Write-Host "  Room $($room.room_number) (type $($room.room_type.id))"

Write-Step "Portal session login"
$cookieJar = Invoke-PortalSession -BaseUrl $PortalUrl -Username $Username -Password $Password

Write-Step "Check in guest through portal"
$today = (Get-Date).ToString("yyyy-MM-dd")
$tomorrow = (Get-Date).AddDays(1).ToString("yyyy-MM-dd")
$checkIn = Invoke-PortalPost -BaseUrl $PortalUrl -CookieJar $cookieJar -Path "/front-desk/check-in" -Fields @{
    guest_name     = "Portal Smoke Guest"
    guest_email    = ""
    room_type_id   = $room.room_type.id
    room_id        = $room.id
    check_in_date  = $today
    check_out_date = $tomorrow
}
if ($checkIn.Status -ne 302 -or $checkIn.Location -notmatch "/folios/(\d+)") {
    throw "Check-in failed (HTTP $($checkIn.Status))."
}
$folioId = [int]$Matches[1]
Write-Host "  Folio #$folioId"

Write-Step "Post F&B order to folio (Phase 2)"
$menu = Invoke-RestMethod -Uri "$ApiS3/menu-items" -Headers $apiHeaders
$burger = $menu.data | Where-Object { $_.code -eq "BURGER-CL" } | Select-Object -First 1
if ($null -eq $burger) {
    throw "Menu item BURGER-CL not found for portal F&B smoke."
}
$openOrder = Invoke-PortalPost -BaseUrl $PortalUrl -CookieJar $cookieJar -Path "/fb/orders" -Fields @{
    folio_id = $folioId
}
if ($openOrder.Status -ne 302 -or $openOrder.Location -notmatch "/orders/(\d+)") {
    throw "F&B order create failed (HTTP $($openOrder.Status))."
}
$orderId = [int]$Matches[1]
$addLine = Invoke-PortalPost -BaseUrl $PortalUrl -CookieJar $cookieJar -Path "/fb/orders/$orderId/lines" -Fields @{
    menu_item_id = $burger.id
    quantity     = 2
}
if ($addLine.Status -notin @(302, 303)) {
    throw "F&B add line failed (HTTP $($addLine.Status))."
}
$finalize = Invoke-PortalPost -BaseUrl $PortalUrl -CookieJar $cookieJar -Path "/fb/orders/$orderId/finalize" -Fields @{}
if ($finalize.Status -ne 302) {
    throw "F&B finalize failed (HTTP $($finalize.Status))."
}
Write-Host "  Order #$orderId finalized on folio #$folioId"

Write-Step "Post incidental charge"
$charge = Invoke-PortalPost -BaseUrl $PortalUrl -CookieJar $cookieJar -Path "/front-desk/folios/$folioId/charges" -Fields @{
    description      = "Minibar snack"
    amount           = "150.00"
    charge_category  = "minibar"
}
if ($charge.Status -notin @(302, 303)) {
    throw "Charge post failed (HTTP $($charge.Status))."
}

$balance = (Invoke-RestMethod -Uri "$ApiS3/folios/$folioId" -Headers $apiHeaders).data.balance
Write-Host "  Balance ETB $balance"

Write-Step "Settle folio"
$settle = Invoke-PortalPost -BaseUrl $PortalUrl -CookieJar $cookieJar -Path "/front-desk/folios/$folioId/settle" -Fields @{
    amount          = $balance
    payment_method  = "cash"
}
if ($settle.Status -notin @(302, 303)) {
    throw "Settle failed (HTTP $($settle.Status))."
}

Write-Step "Check out guest"
$checkout = Invoke-PortalPost -BaseUrl $PortalUrl -CookieJar $cookieJar -Path "/front-desk/folios/$folioId/check-out" -Fields @{}
if ($checkout.Status -ne 302) {
    throw "Check-out failed (HTTP $($checkout.Status))."
}

Remove-Item $cookieJar -Force -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "Portal smoke passed (folio #$folioId)." -ForegroundColor Green
