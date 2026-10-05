# One-time setup on Windows (see docs/LOCAL-SETUP.md). Run it by double-clicking setup.bat.
# Safe to run again at any time: every step skips itself if it was already done.
# Kept ASCII-only on purpose: Windows PowerShell 5.1 misreads UTF-8 files without a BOM.

$ErrorActionPreference = 'Continue'
Set-Location (Split-Path -Parent $PSScriptRoot)

function Say([string]$Message) { Write-Host "`n==> $Message" -ForegroundColor Cyan }
function Fail([string]$Message) { Write-Host "`nERROR: $Message" -ForegroundColor Red; exit 1 }
function Need([string]$Command, [string]$Hint) {
    if (-not (Get-Command $Command -ErrorAction SilentlyContinue)) { Fail "'$Command' was not found. $Hint" }
}
# Native commands don't throw in PowerShell - check the exit code of every step explicitly.
function Run([string]$Exe, [string[]]$Arguments) {
    & $Exe @Arguments
    if ($LASTEXITCODE -ne 0) { Fail "'$Exe $($Arguments -join ' ')' failed (exit code $LASTEXITCODE)." }
}

Say 'Checking requirements'
Need 'php' 'Install Laravel Herd (https://herd.laravel.com/windows) - it adds PHP and Composer - then open a NEW window.'
Need 'composer' 'Install Laravel Herd (https://herd.laravel.com/windows) - it adds PHP and Composer - then open a NEW window.'
Need 'node' 'Install Node.js LTS: winget install OpenJS.NodeJS.LTS  (then open a NEW window)'
Need 'npm' 'Install Node.js LTS: winget install OpenJS.NodeJS.LTS  (then open a NEW window)'
$phpVersion = [int](& php -r 'echo PHP_MAJOR_VERSION * 100 + PHP_MINOR_VERSION;')
if ($phpVersion -lt 803) { Fail "PHP 8.3 or newer is required (found $(& php -r 'echo PHP_VERSION;')). Pick a newer PHP in Herd." }

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

Say "AI model ($model)"
if (-not (Get-Command 'ollama' -ErrorAction SilentlyContinue)) {
    Write-Host 'Ollama is not installed - the app works without it, only the AI features stay off.'
    Write-Host "Install it (winget install Ollama.Ollama or https://ollama.com), then run:  ollama pull $model"
} else {
    $installed = & ollama list 2>$null | Select-Object -Skip 1 | ForEach-Object { ($_ -split '\s+')[0] }
    if (($installed -contains $model) -or ($installed -contains "$($model):latest")) {
        Write-Host 'Already downloaded.'
    } else {
        Write-Host 'Downloading (about 5 GB for qwen3:8b - one time only)...'
        & ollama pull $model
        if ($LASTEXITCODE -ne 0) { Write-Host "Download failed - open the Ollama app, then run:  ollama pull $model" -ForegroundColor Yellow }
    }
}

Say 'Done'
Write-Host 'Start the app any time by double-clicking local\start.bat  (it opens http://127.0.0.1:8010)'
exit $setupStatus
