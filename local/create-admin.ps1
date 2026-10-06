# Creates the app login, or changes its password (same email = same account).
# Run it by double-clicking create-admin.bat. See docs/LOCAL-SETUP.md.
# Kept ASCII-only on purpose: Windows PowerShell 5.1 misreads UTF-8 files without a BOM.

$ErrorActionPreference = 'Continue'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root
. (Join-Path $PSScriptRoot 'runtime.ps1')
Use-LocalRuntime $Root
Disable-QuickEdit

if (-not (Get-Command 'php' -ErrorAction SilentlyContinue) -or -not (Test-Path 'vendor\autoload.php')) {
    Fail 'The app is not set up yet - run install-windows.bat first.'
}

Write-Host 'Type the SAME email as before to change its password, or a new email to make a new login.'
if ([bool](Invoke-AdminAccountPrompt | Select-Object -Last 1)) {
    Say 'Saved - use it on the login page in the browser.'
    exit 0
}
Fail 'The login was not saved - see the messages above.'
