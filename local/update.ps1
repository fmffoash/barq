# Gets the latest code from GitHub and rebuilds everything - the local version of the server
# deploy (see docs/LOCAL-SETUP.md). Your data (database + uploaded images) is not touched.
# Run it by double-clicking update.bat (close the app window first).
# Kept ASCII-only on purpose: Windows PowerShell 5.1 misreads UTF-8 files without a BOM.

$ErrorActionPreference = 'Continue'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root
. (Join-Path $PSScriptRoot 'runtime.ps1')
Use-LocalRuntime $Root
Disable-QuickEdit

$git = Get-Command 'git' -ErrorAction SilentlyContinue
if (-not $git -and $env:ProgramFiles -and (Test-Path (Join-Path $env:ProgramFiles 'Git\cmd\git.exe'))) {
    $git = Get-Command (Join-Path $env:ProgramFiles 'Git\cmd\git.exe')
}
if (-not $git) { Fail 'Git is not installed - run install-windows.bat again (it installs Git and updates the app).' }

Say 'Getting the latest code (git pull)'
Run $git.Source @('pull', '--ff-only')

# The new code may need newer PHP / Node.js than the ones in place.
Initialize-Runtime $Root

Say 'PHP packages'
Run 'composer' @('install', '--no-interaction')

Say 'Interface (npm ci + npm run build)'
Run 'npm' @('ci')
Run 'npm' @('run', 'build')

Say 'Database and caches'
Run 'php' @('artisan', 'optimize:clear')
Run 'php' @('artisan', 'migrate', '--force')
# Library updates (new template copy/colors/fonts) - safe for existing projects, skipped when up to date.
Run 'php' @('artisan', 'barq:seed-template-library', '--if-outdated')

Remove-Item (Join-Path $Root '.runtime\downloads') -Recurse -Force -ErrorAction SilentlyContinue

& php artisan barq:doctor

Say 'Updated'
Write-Host 'Open the app again with the desktop icon.'
