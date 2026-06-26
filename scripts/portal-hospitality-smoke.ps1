# Portal S3 hospitality smoke — login and load all staff-facing S3 Inertia index pages.
# Exit 0 when super.admin can open the hospitality UI surface built in portal Phases 1–6.

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot
. (Join-Path $PSScriptRoot "lib\PortalSession.ps1")

function Write-Step([string]$Message) {
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Get-PortalPageWithRetry {
    param(
        [string]$BaseUrl,
        [string]$CookieJar,
        [string]$Path,
        [string]$Label,
        [int]$MaxAttempts = 3
    )

    for ($attempt = 1; $attempt -le $MaxAttempts; $attempt++) {
        $response = Invoke-PortalGet -BaseUrl $BaseUrl -CookieJar $CookieJar -Path $Path
        if ($response.Status -eq 200) {
            return $response
        }

        if ($attempt -lt $MaxAttempts) {
            Write-Host "  RETRY $Label (HTTP $($response.Status), attempt $attempt/$MaxAttempts)" -ForegroundColor Yellow
            Start-Sleep -Seconds 3
        }
        else {
            return $response
        }
    }
}

function Assert-PortalPage {
    param(
        [string]$Label,
        [hashtable]$Response,
        [string]$ExpectedComponent
    )

    if ($Response.Status -ne 200) {
        throw "$Label returned HTTP $($Response.Status) (location: $($Response.Location))"
    }

    if ($ExpectedComponent) {
        $normalized = $Response.Body -replace '\\/', '/'
        if ($normalized -notmatch [regex]::Escape($ExpectedComponent)) {
            throw "$Label loaded but Inertia component not found: $ExpectedComponent"
        }
    }

    Write-Host "  OK $Label" -ForegroundColor Green
}

function Prepare-SuperAdminAccount {
    $prevEap = $ErrorActionPreference
    try {
        $ErrorActionPreference = "Continue"
        docker compose exec -T s1-identity php artisan app:sync-super-admin 2>$null | Out-Null
    }
    finally {
        $ErrorActionPreference = $prevEap
    }
    if ($LASTEXITCODE -ne 0) {
        throw "Failed to prepare super.admin (run: docker compose exec s1-identity php artisan app:sync-super-admin)."
    }
}

Import-RepoEnv -Root $RepoRoot

$GatewayHost = if ($env:GATEWAY_HOST) { $env:GATEWAY_HOST } else { "127.0.0.1" }
$PortalUrl = "http://$GatewayHost"
$ApiS1 = "http://$GatewayHost/s1/api/v1"
$ApiS3 = "http://$GatewayHost/s3/api/v1"
$Username = "super.admin"
$Password = Get-RepoEnvValue -Root $RepoRoot -Name "SUPER_ADMIN_PASSWORD" -Default "ChangeMeNow!10"

Write-Step "Prepare super.admin"
Prepare-SuperAdminAccount

Write-Step "Portal session login ($Username)"
$cookieJar = Invoke-PortalSession -BaseUrl $PortalUrl -Username $Username -Password $Password

$auth = Invoke-RestMethod -Method POST -Uri "$ApiS1/auth/login" -ContentType "application/json" `
    -Body (@{ username = $Username; password = $Password } | ConvertTo-Json)
$apiHeaders = @{ Authorization = "Bearer $($auth.access_token)" }

try {
    Write-Step "Front desk"
    $frontDeskPages = @(
        @{ Label = "Rooms"; Path = "/front-desk/rooms"; Component = "FrontDesk/Rooms/Index" },
        @{ Label = "Reservations"; Path = "/front-desk/reservations"; Component = "FrontDesk/Reservations/Index" },
        @{ Label = "Book reservation"; Path = "/front-desk/reservations/create"; Component = "FrontDesk/Reservations/Create" },
        @{ Label = "Guests"; Path = "/front-desk/guests"; Component = "FrontDesk/Guests/Index" },
        @{ Label = "Guest create"; Path = "/front-desk/guests/create"; Component = "FrontDesk/Guests/Edit" },
        @{ Label = "Check in"; Path = "/front-desk/check-in"; Component = "FrontDesk/CheckIn/Create" },
        @{ Label = "Folios"; Path = "/front-desk/folios"; Component = "FrontDesk/Folios/Index" },
        @{ Label = "Cashier shifts"; Path = "/front-desk/cashier-shifts"; Component = "FrontDesk/CashierShifts/Index" },
        @{ Label = "Hotel settings"; Path = "/front-desk/settings"; Component = "FrontDesk/Settings/Index" },
        @{ Label = "Physical rooms"; Path = "/front-desk/settings/rooms"; Component = "FrontDesk/Settings/Rooms" }
    )

    foreach ($page in $frontDeskPages) {
        $response = Get-PortalPageWithRetry -BaseUrl $PortalUrl -CookieJar $cookieJar -Path $page.Path -Label $page.Label
        Assert-PortalPage -Label $page.Label -Response $response -ExpectedComponent $page.Component
    }

    Write-Step "Restaurant and F&B"
    $fbPages = @(
        @{ Label = "Menu"; Path = "/fb/menu"; Component = "Fb/Menu/Index" },
        @{ Label = "Orders"; Path = "/fb/orders"; Component = "Fb/Orders/Index" },
        @{ Label = "New order"; Path = "/fb/orders/create"; Component = "Fb/Orders/Create" },
        @{ Label = "Catalog admin"; Path = "/fb/settings"; Component = "Fb/Settings/Index" },
        @{ Label = "Menu categories"; Path = "/fb/menu-categories"; Component = "Fb/MenuCategories/Index" },
        @{ Label = "Menu items"; Path = "/fb/menu-items"; Component = "Fb/MenuItems/Index" },
        @{ Label = "Menu item create"; Path = "/fb/menu-items/create"; Component = "Fb/MenuItems/Create" },
        @{ Label = "Dining tables"; Path = "/fb/dining-tables"; Component = "Fb/DiningTables/Index" }
    )

    foreach ($page in $fbPages) {
        $response = Get-PortalPageWithRetry -BaseUrl $PortalUrl -CookieJar $cookieJar -Path $page.Path -Label $page.Label
        Assert-PortalPage -Label $page.Label -Response $response -ExpectedComponent $page.Component
    }

    Write-Step "Inventory and procurement"
    $inventoryPages = @(
        @{ Label = "Items"; Path = "/inventory/items"; Component = "Inventory/Items/Index" },
        @{ Label = "New item"; Path = "/inventory/items/create"; Component = "Inventory/Items/Create" },
        @{ Label = "Item categories"; Path = "/inventory/item-categories"; Component = "Inventory/ItemCategories/Index" },
        @{ Label = "Stock alerts"; Path = "/inventory/alerts"; Component = "Inventory/Alerts/Index" },
        @{ Label = "Valuation"; Path = "/inventory/valuation"; Component = "Inventory/Valuation/Index" },
        @{ Label = "Suppliers"; Path = "/inventory/suppliers"; Component = "Inventory/Suppliers/Index" },
        @{ Label = "New supplier"; Path = "/inventory/suppliers/create"; Component = "Inventory/Suppliers/Create" },
        @{ Label = "Purchase orders"; Path = "/inventory/purchase-orders"; Component = "Inventory/PurchaseOrders/Index" },
        @{ Label = "Create PO"; Path = "/inventory/purchase-orders/create"; Component = "Inventory/PurchaseOrders/Create" }
    )

    foreach ($page in $inventoryPages) {
        $response = Get-PortalPageWithRetry -BaseUrl $PortalUrl -CookieJar $cookieJar -Path $page.Path -Label $page.Label
        Assert-PortalPage -Label $page.Label -Response $response -ExpectedComponent $page.Component
    }

    $item = (Invoke-RestMethod -Uri "$ApiS3/items" -Headers $apiHeaders).data | Select-Object -First 1
    if ($null -ne $item) {
        $itemShow = Get-PortalPageWithRetry -BaseUrl $PortalUrl -CookieJar $cookieJar -Path "/inventory/items/$($item.id)" -Label "Item #$($item.id)"
        Assert-PortalPage -Label "Item #$($item.id)" -Response $itemShow -ExpectedComponent "Inventory/Items/Show"
    }
    else {
        Write-Host "  SKIP item show (no inventory items)" -ForegroundColor Yellow
    }

    Write-Step "Staff meals and group bookings"
    $otherPages = @(
        @{ Label = "Consumption periods"; Path = "/consumption/periods"; Component = "Consumption/Periods/Index" },
        @{ Label = "Group bookings"; Path = "/group-bookings"; Component = "GroupBookings/Index" },
        @{ Label = "Create group"; Path = "/group-bookings/create"; Component = "GroupBookings/Create" }
    )

    foreach ($page in $otherPages) {
        $response = Get-PortalPageWithRetry -BaseUrl $PortalUrl -CookieJar $cookieJar -Path $page.Path -Label $page.Label
        Assert-PortalPage -Label $page.Label -Response $response -ExpectedComponent $page.Component
    }

    $shift = (Invoke-RestMethod -Uri "$ApiS3/cashier-shifts" -Headers $apiHeaders).data.data | Select-Object -First 1
    if ($null -ne $shift) {
        $shiftShow = Get-PortalPageWithRetry -BaseUrl $PortalUrl -CookieJar $cookieJar -Path "/front-desk/cashier-shifts/$($shift.id)" -Label "Cashier shift #$($shift.id)"
        Assert-PortalPage -Label "Cashier shift #$($shift.id)" -Response $shiftShow -ExpectedComponent "FrontDesk/CashierShifts/Show"
    }
    else {
        Write-Host "  SKIP cashier shift show (no shifts)" -ForegroundColor Yellow
    }
}
finally {
    Remove-Item $cookieJar -Force -ErrorAction SilentlyContinue
}

Write-Host ""
Write-Host "Portal hospitality smoke passed (S3 UI Phases 1-6)." -ForegroundColor Green
