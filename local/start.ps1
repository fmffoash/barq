# Starts the app on http://127.0.0.1:8010 and opens it in the browser (see docs/LOCAL-SETUP.md).
# Run it by double-clicking start.bat. Keep the window open while you work; closing it stops the app.
# Kept ASCII-only on purpose: Windows PowerShell 5.1 misreads UTF-8 files without a BOM.

$ErrorActionPreference = 'Continue'
Set-Location (Split-Path -Parent $PSScriptRoot)

$Port = if ($env:BARQ_PORT) { $env:BARQ_PORT } else { '8010' }
$Url = "http://127.0.0.1:$Port"

function Test-Url([string]$Target) {
    try { Invoke-WebRequest -Uri $Target -UseBasicParsing -TimeoutSec 2 | Out-Null; return $true } catch { return $false }
}

if (Test-Url "$Url/up") {
    Write-Host "Already running at $Url"
    Start-Process $Url
    exit 0
}

if (-not (Test-Path '.env')) {
    Write-Host 'Not set up yet - double-click local\setup.bat first.' -ForegroundColor Red
    exit 1
}

if ((Get-Command 'ollama' -ErrorAction SilentlyContinue) -and -not (Test-Url 'http://127.0.0.1:11434/api/tags')) {
    Write-Host 'Starting Ollama in the background...'
    Start-Process 'ollama' -ArgumentList 'serve' -WindowStyle Hidden
}

# Opens the browser as soon as the server answers, while the server itself runs in the
# foreground below (so closing this window stops it).
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

Write-Host "Starting on $Url - keep this window open while you work (close it to stop)."
& php artisan serve --host=127.0.0.1 --port=$Port
