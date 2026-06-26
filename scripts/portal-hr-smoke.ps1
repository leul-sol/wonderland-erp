# Portal S2 HR & payroll smoke — login and load all staff-facing HR/payroll Inertia pages.
# Exit 0 when super.admin can open the S2 UI surface built in portal Phases 1–6.

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
$ApiS1 = "http://$GatewayHost/s1/api/v1"
$ApiS2 = "http://$GatewayHost/s2/api/v1"
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
    Write-Step "HR workforce pages"
    $pages = @(
        @{ Label = "Employees"; Path = "/hr/employees"; Component = "Hr/Employees/Index" },
        @{ Label = "Employee create"; Path = "/hr/employees/create"; Component = "Hr/Employees/Create" },
        @{ Label = "Departments"; Path = "/hr/departments"; Component = "Hr/Organization/Departments/Index" },
        @{ Label = "Positions"; Path = "/hr/positions"; Component = "Hr/Organization/Positions/Index" },
        @{ Label = "Overtime queue"; Path = "/hr/overtime"; Component = "Hr/Overtime/Index" },
        @{ Label = "Offboarding"; Path = "/hr/offboarding"; Component = "Hr/Offboarding/Index" },
        @{ Label = "Leave requests"; Path = "/hr/leave-requests"; Component = "Hr/Leave/Index" },
        @{ Label = "Attendance"; Path = "/hr/attendance"; Component = "Hr/Attendance/Index" },
        @{ Label = "HR settings"; Path = "/hr/settings"; Component = "Hr/Settings/Index" }
    )

    foreach ($page in $pages) {
        $response = Invoke-PortalGet -BaseUrl $PortalUrl -CookieJar $cookieJar -Path $page.Path
        Assert-PortalPage -Label $page.Label -Response $response -ExpectedComponent $page.Component
    }

    $employee = (Invoke-RestMethod -Uri "$ApiS2/employees?status=active" -Headers $apiHeaders).data | Select-Object -First 1
    if ($null -ne $employee) {
        $showPath = "/hr/employees/$($employee.id)"
        $show = Invoke-PortalGet -BaseUrl $PortalUrl -CookieJar $cookieJar -Path $showPath
        Assert-PortalPage -Label "Employee hub #$($employee.id)" -Response $show -ExpectedComponent "Hr/Employees/Show"
    }
    else {
        Write-Host "  SKIP employee hub (no active employees)" -ForegroundColor Yellow
    }

    Write-Step "Payroll pages"
    $payrollPages = @(
        @{ Label = "Payroll runs"; Path = "/payroll/runs"; Component = "Payroll/Runs/Index" },
        @{ Label = "Create payroll run"; Path = "/payroll/runs/create"; Component = "Payroll/Runs/Create" },
        @{ Label = "Severance"; Path = "/payroll/severance"; Component = "Payroll/Severance/Index" }
    )

    foreach ($page in $payrollPages) {
        $response = Invoke-PortalGet -BaseUrl $PortalUrl -CookieJar $cookieJar -Path $page.Path
        Assert-PortalPage -Label $page.Label -Response $response -ExpectedComponent $page.Component
    }
}
finally {
    Remove-Item $cookieJar -Force -ErrorAction SilentlyContinue
}

Write-Host ""
Write-Host "Portal HR and payroll smoke passed (S2 UI Phases 1-6)." -ForegroundColor Green
