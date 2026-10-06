# Starts the app and opens it in the browser (see docs/LOCAL-SETUP.md). Run by the desktop icon
# or start.bat. The (minimized) window runs the app: closing it stops the app.
# Kept ASCII-only on purpose: Windows PowerShell 5.1 misreads UTF-8 files without a BOM.

$ErrorActionPreference = 'Continue'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root
. (Join-Path $PSScriptRoot 'runtime.ps1')
Use-LocalRuntime $Root
Disable-QuickEdit

$Port = Get-AppPort $Root
$Url = "http://127.0.0.1:$Port"
$Router = Join-Path $PSScriptRoot 'server.php'

function Test-Up {
    try { Invoke-WebRequest -Uri "$Url/up" -UseBasicParsing -TimeoutSec 2 | Out-Null; return $true } catch { return $false }
}

function Test-PortOpen {
    $client = New-Object Net.Sockets.TcpClient
    try { return $client.ConnectAsync('127.0.0.1', [int]$Port).Wait(700) } catch { return $false } finally { $client.Dispose() }
}

# Our own server, possibly busy: on Windows it answers one request at a time, so /up can time
# out while it waits for a long AI reply.
function Test-OwnServer {
    if (-not $script:OnWindows) { return $false }
    try {
        return [bool](Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" -ErrorAction Stop |
            Where-Object { $_.CommandLine -and $_.CommandLine.Contains($Router) })
    } catch {
        return $false
    }
}

if ((Test-Up) -or ((Test-PortOpen) -and (Test-OwnServer))) {
    Write-Host "Already running at $Url"
    Start-Process $Url
    exit 0
}

if ((Test-PortOpen)) {
    Show-Error "Port $Port is used by another program, so the app can't start. Close that program (or restart the computer), or change APP_URL in C:\barq\.env to another port such as http://127.0.0.1:8020."
    exit 1
}

if (-not (Test-Path '.env') -or -not (Test-Path 'vendor\autoload.php')) {
    Show-Error 'The app is not set up yet. Run install-windows.bat (or local\setup.bat) first.'
    exit 1
}

if (-not (Get-Command 'php' -ErrorAction SilentlyContinue)) {
    if (Test-Path (Join-Path $Root '.runtime\php')) {
        Show-Error "php.exe is missing from $Root\.runtime\php - an antivirus probably removed it. Add $Root to the antivirus exclusions, then run install-windows.bat again."
    } else {
        Show-Error 'PHP was not found. Run install-windows.bat again.'
    }
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
    -S "127.0.0.1:$Port" -t public $Router
$code = $LASTEXITCODE

# -1073741510 = stopped with Ctrl+C. Anything else non-zero means it could not start or crashed.
if ($code -ne 0 -and $code -ne -1073741510) {
    Show-Error "The app stopped unexpectedly (code $code). If the window says 'Failed to listen', port $Port is busy - change APP_URL in $Root\.env to another port."
    exit $code
}
exit 0
