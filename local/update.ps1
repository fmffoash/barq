# Gets the latest code from GitHub and rebuilds everything - the local version of the server
# deploy (see docs/LOCAL-SETUP.md). Your data (database + uploaded images) is not touched.
# Run it by double-clicking update.bat (close the start.bat window first).
# Kept ASCII-only on purpose: Windows PowerShell 5.1 misreads UTF-8 files without a BOM.

$ErrorActionPreference = 'Continue'
Set-Location (Split-Path -Parent $PSScriptRoot)

function Say([string]$Message) { Write-Host "`n==> $Message" -ForegroundColor Cyan }
function Fail([string]$Message) { Write-Host "`nERROR: $Message" -ForegroundColor Red; exit 1 }
function Run([string]$Exe, [string[]]$Arguments) {
    & $Exe @Arguments
    if ($LASTEXITCODE -ne 0) { Fail "'$Exe $($Arguments -join ' ')' failed (exit code $LASTEXITCODE)." }
}

Say 'Getting the latest code (git pull)'
Run 'git' @('pull', '--ff-only')

Say 'PHP packages'
Run 'composer' @('install', '--no-interaction')

Say 'Interface (npm ci + npm run build)'
Run 'npm' @('ci')
Run 'npm' @('run', 'build')

Say 'Database and caches'
Run 'php' @('artisan', 'optimize:clear')
Run 'php' @('artisan', 'migrate', '--force')

& php artisan barq:doctor

Say 'Updated'
Write-Host 'Start the app again by double-clicking local\start.bat'
