# Portal S1 admin smoke — login, users, roles, audit log, change-password page.
# Exit 0 when all admin Inertia pages load for super.admin.

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
        throw "Failed to prepare super.admin for smoke (run: docker compose exec s1-identity php artisan app:sync-super-admin)."
    }
}

Import-RepoEnv -Root $RepoRoot

$GatewayHost = if ($env:GATEWAY_HOST) { $env:GATEWAY_HOST } else { "127.0.0.1" }
$PortalUrl = "http://$GatewayHost"
$Username = "super.admin"
$Password = Get-RepoEnvValue -Root $RepoRoot -Name "SUPER_ADMIN_PASSWORD" -Default "ChangeMeNow!10"

Write-Step "Prepare super.admin for admin navigation"
Prepare-SuperAdminAccount

Write-Step "Portal session login ($Username)"
$cookieJar = Invoke-PortalSession -BaseUrl $PortalUrl -Username $Username -Password $Password

try {
    Write-Step "Change password page"
    $changePassword = Invoke-PortalGet -BaseUrl $PortalUrl -CookieJar $cookieJar -Path "/account/change-password"
    Assert-PortalPage -Label "Change password" -Response $changePassword -ExpectedComponent "Auth/ChangePassword"

    Write-Step "S1 admin pages"
    $users = Invoke-PortalGet -BaseUrl $PortalUrl -CookieJar $cookieJar -Path "/admin/users"
    Assert-PortalPage -Label "Users" -Response $users -ExpectedComponent "Admin/Users/Index"

    $roles = Invoke-PortalGet -BaseUrl $PortalUrl -CookieJar $cookieJar -Path "/admin/roles"
    Assert-PortalPage -Label "Roles" -Response $roles -ExpectedComponent "Admin/Roles/Index"

    $audit = Invoke-PortalGet -BaseUrl $PortalUrl -CookieJar $cookieJar -Path "/admin/audit-logs"
    Assert-PortalPage -Label "Audit log" -Response $audit -ExpectedComponent "Admin/Audit/Index"
}
finally {
    Remove-Item $cookieJar -Force -ErrorAction SilentlyContinue
}

Write-Host ""
Write-Host "Portal admin smoke passed (login, users, roles, audit, change-password)." -ForegroundColor Green
