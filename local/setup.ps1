# One-time setup on Windows (see docs/LOCAL-SETUP.md). Run it by double-clicking setup.bat
# (install-windows.bat calls it too). Safe to run again: every step skips itself if done.
# Kept ASCII-only on purpose: Windows PowerShell 5.1 misreads UTF-8 files without a BOM.

$ErrorActionPreference = 'Continue'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root
. (Join-Path $PSScriptRoot 'runtime.ps1')
Use-LocalRuntime $Root

$herdHint = 'Or install Laravel Herd (https://herd.laravel.com/windows) and run this again.'

Say 'PHP'
if (Test-Php) {
    Write-Host "Using $((Get-Command 'php').Source)"
} else {
    Write-Host 'Not found (or too old) - downloading a private copy for this app...'
    if (-not (Install-PortablePhp $Root)) { Fail "Could not download PHP automatically. Check the internet connection and run setup again. $herdHint" }
    Use-LocalRuntime $Root
    if (-not (Test-Php)) { Fail "The downloaded PHP does not start correctly. $herdHint" }
}

Say 'Composer'
if (Get-Command 'composer' -ErrorAction SilentlyContinue) {
    Write-Host 'Already installed.'
} else {
    if (-not (Install-PortableComposer $Root)) { Fail "Could not download Composer. Check the internet connection and run setup again. $herdHint" }
    Use-LocalRuntime $Root
}

Say 'Node.js'
if ((Get-Command 'node' -ErrorAction SilentlyContinue) -and (Get-Command 'npm' -ErrorAction SilentlyContinue)) {
    Write-Host "Using $(& node -v)"
} else {
    Write-Host 'Not found - downloading a private copy for this app...'
    if (-not (Install-PortableNode $Root)) { Fail 'Could not download Node.js. Check the internet connection and run setup again.' }
    Use-LocalRuntime $Root
}

if (-not (Test-Path '.env')) {
    Say 'Creating .env from .env.example'
    Copy-Item '.env.example' '.env'
}

Say 'Installing PHP packages (composer install)'
Run 'composer' @('install', '--no-interaction')

if (-not (Select-String -Path '.env' -Pattern '^APP_KEY=base64:' -Quiet)) {
    Run 'php' @('artisan', 'key:generate')
}

Say 'Installing and building the interface (npm ci + npm run build)'
Run 'npm' @('ci')
Run 'npm' @('run', 'build')

Say 'Preparing database, template library and admin account'
& php artisan barq:local-setup
$setupStatus = $LASTEXITCODE

$model = 'qwen3:8b'
$modelLine = Get-Content '.env' -Encoding UTF8 | Where-Object { $_ -match '^OLLAMA_MODEL=' } | Select-Object -Last 1
if ($modelLine) {
    $value = ($modelLine -replace '^OLLAMA_MODEL=', '').Trim().Trim('"').Trim("'")
    if ($value) { $model = $value }
}

Say "AI (Ollama + model $model)"
$haveOllama = [bool](Get-Command 'ollama' -ErrorAction SilentlyContinue)
if (-not $haveOllama -and $script:OnWindows) {
    Write-Host 'Installing Ollama...'
    $haveOllama = Install-Ollama $Root
}
if (-not $haveOllama) {
    Warn 'Ollama is not installed - the app works without it, only the AI features stay off.'
    Warn "Install it from https://ollama.com, then run:  ollama pull $model"
} elseif (-not (Start-OllamaIfNeeded)) {
    Warn "Ollama is installed but not running - open the Ollama app, then run:  ollama pull $model"
} else {
    $installed = & ollama list 2>$null | Select-Object -Skip 1 | ForEach-Object { ($_ -split '\s+')[0] }
    if (($installed -contains $model) -or ($installed -contains "$($model):latest")) {
        Write-Host 'Model already downloaded.'
    } else {
        Write-Host 'Downloading the model (about 5 GB for qwen3:8b - one time only)...'
        & ollama pull $model
        if ($LASTEXITCODE -ne 0) { Warn "Download failed - run later:  ollama pull $model" }
    }
}

if ($script:OnWindows) {
    Say 'Desktop shortcut'
    New-AppShortcuts $Root
    Write-Host 'Added an icon to the desktop and the Start menu.'
}

Say 'Done'
Write-Host 'Open the app with the new desktop icon (or local\start.bat). It opens http://127.0.0.1:8010'
exit $setupStatus
