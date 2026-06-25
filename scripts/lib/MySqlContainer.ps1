# Shared helpers for running ops/*.sh scripts inside wh-mysql from Windows hosts.

function Import-RepoEnv {
    param([string]$Root)

    $path = Join-Path $Root ".env"
    if (-not (Test-Path $path)) { return }

    Get-Content $path | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq "" -or $line.StartsWith("#")) { return }
        $eq = $line.IndexOf("=")
        if ($eq -lt 1) { return }
        $name = $line.Substring(0, $eq).Trim()
        $value = $line.Substring($eq + 1).Trim().Trim('"').Trim("'")
        Set-Item -Path "env:$name" -Value $value
    }
}

function Invoke-MySqlContainerScript {
    param(
        [string]$RepoRoot,
        [string]$RelativeScript,
        [string[]]$Arguments = @(),
        [hashtable]$Environment = @{}
    )

    $source = Join-Path $RepoRoot $RelativeScript
    if (-not (Test-Path $source)) {
        throw "Script not found: $source"
    }

    $content = [System.IO.File]::ReadAllText($source) -replace "`r", ""
    $tempFile = Join-Path $env:TEMP ("wh-" + [IO.Path]::GetFileName($RelativeScript))
    [System.IO.File]::WriteAllText($tempFile, $content, [System.Text.UTF8Encoding]::new($false))

    $containerId = docker compose ps -q wh-mysql
    if (-not $containerId) {
        throw "wh-mysql container is not running."
    }

    $remotePath = "/tmp/" + [IO.Path]::GetFileName($RelativeScript)
    docker cp "$tempFile" "${containerId}:${remotePath}" | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Failed to copy script into wh-mysql container."
    }

    $envArgs = @()
    foreach ($key in $Environment.Keys) {
        $envArgs += "-e"
        $envArgs += "${key}=$($Environment[$key])"
    }

    $cmd = @("compose", "exec", "-T") + $envArgs + @("wh-mysql", "sh", $remotePath) + $Arguments
    $prevErrorAction = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    try {
        & docker @cmd 2>&1 | ForEach-Object { Write-Host $_ }
        return $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $prevErrorAction
    }
}

function Invoke-MySqlInlineScript {
    param(
        [string]$Script,
        [hashtable]$Environment = @{}
    )

    $content = $Script -replace "`r", ""
    $tempFile = Join-Path $env:TEMP ("wh-inline-" + [guid]::NewGuid().ToString("n") + ".sh")
    [System.IO.File]::WriteAllText($tempFile, $content, [System.Text.UTF8Encoding]::new($false))

    $containerId = docker compose ps -q wh-mysql
    if (-not $containerId) {
        throw "wh-mysql container is not running."
    }

    $remotePath = "/tmp/" + [IO.Path]::GetFileName($tempFile)
    docker cp "$tempFile" "${containerId}:${remotePath}" | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Failed to copy inline script into wh-mysql container."
    }

    $envArgs = @()
    foreach ($key in $Environment.Keys) {
        $envArgs += "-e"
        $envArgs += "${key}=$($Environment[$key])"
    }

    $cmd = @("compose", "exec", "-T") + $envArgs + @("wh-mysql", "sh", $remotePath)
    $prevErrorAction = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    try {
        & docker @cmd 2>&1 | ForEach-Object { Write-Host $_ }
        return $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $prevErrorAction
        Remove-Item $tempFile -Force -ErrorAction SilentlyContinue
    }
}
