# One-time setup on Windows (see docs/LOCAL-SETUP.md). Run by install-windows.bat or setup.bat.
# Safe to run again: every step skips itself if it was already done.
# Kept ASCII-only on purpose: Windows PowerShell 5.1 misreads UTF-8 files without a BOM.

$ErrorActionPreference = 'Continue'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root
. (Join-Path $PSScriptRoot 'runtime.ps1')
Use-LocalRuntime $Root
Disable-QuickEdit
Protect-AppFolder $Root

Initialize-Runtime $Root

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

Say 'Preparing the database and the template library'
Run 'php' @('artisan', 'barq:local-setup', '--skip-admin')

$incomplete = @()

& php artisan barq:create-admin --check | Out-Null
if ($LASTEXITCODE -ne 0) {
    Say 'Your login'
    if (-not [bool](Invoke-AdminAccountPrompt | Select-Object -Last 1)) {
        $incomplete += 'the login was not created - double-click local\create-admin.bat to create it'
    }
}

# The app is usable from here on, so the icon comes before the big (optional) AI download.
if ($script:OnWindows) {
    Say 'Desktop shortcut'
    New-AppShortcuts $Root
    Write-Host 'Added an icon to the desktop and the Start menu.'
}

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
    $haveOllama = [bool](Install-Ollama $Root | Select-Object -Last 1)
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
        if ($LASTEXITCODE -ne 0) { Warn "Download failed - run the installer again later, or:  ollama pull $model" }
    }
}

Remove-Item (Join-Path $Root '.runtime\downloads') -Recurse -Force -ErrorAction SilentlyContinue

& php artisan barq:doctor
if ($LASTEXITCODE -ne 0) { $incomplete += 'the health check above shows FAIL lines' }

if ($incomplete.Count -gt 0) {
    Write-Host "`nSETUP IS NOT COMPLETE:" -ForegroundColor Red
    $incomplete | ForEach-Object { Write-Host " - $_" -ForegroundColor Red }
    Write-Host 'Fix that (or run install-windows.bat again), then open the app from the desktop icon.' -ForegroundColor Red
    exit 1
}

Say 'Done'
Write-Host 'Open the app with the new desktop icon. It opens http://127.0.0.1:8010 in your browser.'
exit 0
