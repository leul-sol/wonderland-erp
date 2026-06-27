# Portal S4 finance smoke — login and load finance Inertia pages (Phases S4–S5).
# Exit 0 when super.admin can open the finance UI surface.

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path $PSScriptRoot -Parent
Set-Location $RepoRoot
. (Join-Path $PSScriptRoot "lib\PortalSession.ps1")

function Write-Step([string]$Message) {
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
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
$Username = "super.admin"
$Password = Get-RepoEnvValue -Root $RepoRoot -Name "SUPER_ADMIN_PASSWORD" -Default "ChangeMeNow!10"

Write-Step "Prepare super.admin"
Prepare-SuperAdminAccount

Write-Step "Portal session login ($Username)"
$cookieJar = Invoke-PortalSession -BaseUrl $PortalUrl -Username $Username -Password $Password

try {
    Write-Step "Finance portal pages"
    $pages = @(
        @{ Label = "Financial reports"; Path = "/finance/reports"; Component = "Finance/Reports/Index" },
        @{ Label = "Chart of accounts"; Path = "/finance/accounts"; Component = "Finance/Accounts/Index" },
        @{ Label = "Journals"; Path = "/finance/journals"; Component = "Finance/Journals/Index" },
        @{ Label = "Fiscal periods"; Path = "/finance/fiscal-periods"; Component = "Finance/FiscalPeriods/Index" },
        @{ Label = "Receivables"; Path = "/finance/receivables"; Component = "Finance/Receivables/Index" },
        @{ Label = "Payables"; Path = "/finance/payables"; Component = "Finance/Payables/Index" },
        @{ Label = "Budget"; Path = "/finance/budget"; Component = "Finance/Budget/Index" },
        @{ Label = "Executive dashboard"; Path = "/finance/dashboard/executive"; Component = "Finance/Dashboard/Executive" },
        @{ Label = "Hotel dashboard"; Path = "/finance/dashboard/hotel"; Component = "Finance/Dashboard/Hotel" },
        @{ Label = "Restaurant dashboard"; Path = "/finance/dashboard/restaurant"; Component = "Finance/Dashboard/Restaurant" },
        @{ Label = "Finance dashboard"; Path = "/finance/dashboard/finance"; Component = "Finance/Dashboard/Finance" },
        @{ Label = "Operations dashboard"; Path = "/finance/dashboard/operations"; Component = "Finance/Dashboard/Operations" },
        @{ Label = "BI catalog"; Path = "/finance/bi-reports"; Component = "Finance/BiReports/Index" },
        @{ Label = "RTM"; Path = "/finance/rtm"; Component = "Finance/Rtm/Index" },
        @{ Label = "UAT"; Path = "/finance/uat"; Component = "Finance/Uat/Index" }
    )

    foreach ($page in $pages) {
        $response = Invoke-PortalGet -BaseUrl $PortalUrl -CookieJar $cookieJar -Path $page.Path
        Assert-PortalPage -Label $page.Label -Response $response -ExpectedComponent $page.Component
    }
}
finally {
    Remove-Item $cookieJar -Force -ErrorAction SilentlyContinue
}

Write-Host ""
Write-Host "Portal finance smoke passed (S4 UI S4-S5)." -ForegroundColor Green
