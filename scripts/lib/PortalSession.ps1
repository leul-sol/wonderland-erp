# Shared curl + cookie helpers for portal smoke scripts (Windows hosts).

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

function Get-RepoEnvValue {
    param(
        [string]$Root,
        [string]$Name,
        [string]$Default
    )

    $path = Join-Path $Root ".env"
    if (-not (Test-Path $path)) {
        return $Default
    }

    foreach ($line in Get-Content $path) {
        if ($line -match "^\s*$Name=(.+)$") {
            return $Matches[1].Trim().Trim('"').Trim("'")
        }
    }

    return $Default
}

function Get-XsrfFromJar {
    param([string]$CookieJar)

    foreach ($line in Get-Content $CookieJar) {
        if ($line -match "^\s*#" -or $line.Trim() -eq "") { continue }
        $parts = $line -split "`t"
        if ($parts.Count -ge 7 -and $parts[5] -eq "XSRF-TOKEN") {
            return [uri]::UnescapeDataString($parts[6])
        }
    }

    throw "XSRF-TOKEN not found in cookie jar"
}

function Invoke-PortalSession {
    param(
        [string]$BaseUrl,
        [string]$Username,
        [string]$Password
    )

    $jar = New-TemporaryFile
    $sink = Join-Path $env:TEMP "portal-session-sink.txt"
    $headerFile = Join-Path $env:TEMP "portal-session-login-headers.txt"
    try {
        curl.exe -s -c $jar.FullName -b $jar.FullName "$BaseUrl/login" -o $sink | Out-Null
        $xsrf = Get-XsrfFromJar -CookieJar $jar.FullName
        $status = curl.exe -s -c $jar.FullName -b $jar.FullName `
            -X POST "$BaseUrl/login" `
            -H "X-XSRF-TOKEN: $xsrf" `
            -H "Accept: text/html" `
            --data-urlencode "username=$Username" `
            --data-urlencode "password=$Password" `
            -D $headerFile `
            -o $sink -w "%{http_code}"

        $headers = Get-Content $headerFile -ErrorAction SilentlyContinue
        $location = ($headers | Select-String -Pattern "^Location:" | Select-Object -Last 1).Line
        $location = if ($location) { ($location -replace "^Location:\s*", "").Trim() } else { "" }

        if ([int]$status -notin @(200, 302)) {
            throw "Portal login failed with HTTP $status."
        }

        if ($location -match '/login(\?|$)') {
            throw "Portal login rejected credentials (redirected to login)."
        }

        return $jar.FullName
    }
    catch {
        Remove-Item $jar.FullName -Force -ErrorAction SilentlyContinue
        throw
    }
    finally {
        Remove-Item $sink, $headerFile -Force -ErrorAction SilentlyContinue
    }
}

function Invoke-PortalGet {
    param(
        [string]$BaseUrl,
        [string]$CookieJar,
        [string]$Path
    )

    $sink = Join-Path $env:TEMP "portal-get-sink.txt"
    $headerFile = Join-Path $env:TEMP "portal-get-headers.txt"

    try {
        $status = curl.exe -s -c $CookieJar -b $CookieJar `
            -H "Accept: text/html" `
            -D $headerFile `
            -o $sink `
            -w "%{http_code}" `
            "$BaseUrl$Path"

        $body = Get-Content $sink -Raw -ErrorAction SilentlyContinue
        $headers = Get-Content $headerFile -ErrorAction SilentlyContinue
        $location = ($headers | Select-String -Pattern "^Location:" | Select-Object -Last 1).Line
        $location = if ($location) { ($location -replace "^Location:\s*", "").Trim() } else { "" }

        return @{
            Status   = [int]$status
            Location = $location
            Body     = $body
        }
    }
    finally {
        Remove-Item $sink, $headerFile -Force -ErrorAction SilentlyContinue
    }
}

function Invoke-PortalPost {
    param(
        [string]$BaseUrl,
        [string]$CookieJar,
        [string]$Path,
        [hashtable]$Fields
    )

    $xsrf = Get-XsrfFromJar -CookieJar $CookieJar
    $sink = Join-Path $env:TEMP "portal-post-sink.txt"
    $headerFile = Join-Path $env:TEMP "portal-post-headers.txt"
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
