# Starts the app on http://127.0.0.1:8010 and opens it in the browser (see docs/LOCAL-SETUP.md).
# Run by the desktop icon or start.bat. The window runs the app: closing it stops the app.
# Kept ASCII-only on purpose: Windows PowerShell 5.1 misreads UTF-8 files without a BOM.

$ErrorActionPreference = 'Continue'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root
. (Join-Path $PSScriptRoot 'runtime.ps1')
Use-LocalRuntime $Root

$Port = if ($env:BARQ_PORT) { $env:BARQ_PORT } else { '8010' }
$Url = "http://127.0.0.1:$Port"

function Test-Up {
    try { Invoke-WebRequest -Uri "$Url/up" -UseBasicParsing -TimeoutSec 2 | Out-Null; return $true } catch { return $false }
}

if (Test-Up) {
    Write-Host "Already running at $Url"
    Start-Process $Url
    exit 0
}

if (-not (Test-Path '.env') -or -not (Test-Path 'vendor') -or -not (Get-Command 'php' -ErrorAction SilentlyContinue)) {
    Write-Host 'Not set up yet - double-click local\setup.bat first.' -ForegroundColor Red
    exit 1
}

$null = Start-OllamaIfNeeded

# Opens the browser as soon as the app answers, while the app itself runs in the foreground
# below (so closing this window stops it).
$null = Start-Job -ArgumentList $Url -ScriptBlock {
    param($Target)
    for ($i = 0; $i -lt 60; $i++) {
        try {
            Invoke-WebRequest -Uri "$Target/up" -UseBasicParsing -TimeoutSec 1 | Out-Null
            Start-Process $Target
            return
        } catch {
            Start-Sleep -Milliseconds 500
        }
    }
}

Write-Host "Running on $Url - keep this window open while you work (close it to stop the app)."
# PHP's own web server (not `artisan serve`, which can't pass -d settings): the app accepts
# images up to 8 MB (PHP's default limit is 2 MB) and AI replies can take over a minute.
& php -d upload_max_filesize=10M -d post_max_size=64M -d max_execution_time=0 -d memory_limit=512M `
    -S "127.0.0.1:$Port" -t public (Join-Path $PSScriptRoot 'server.php')
