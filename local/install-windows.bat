@echo off
rem ==========================================================================
rem  One-file installer for Windows - see docs\LOCAL-SETUP.md
rem  Double-click it: it downloads the app to C:\barq, installs what it needs
rem  and puts an icon on the desktop. Running it again updates the app.
rem ==========================================================================
setlocal
set "TARGET=C:\barq"
set "REPO=https://github.com/fmffoash/barq.git"

call :find_git
if defined GIT goto have_git
echo Installing Git - if Windows asks for permission, click Yes...
winget install --id Git.Git -e --source winget --accept-package-agreements --accept-source-agreements
call :find_git
if defined GIT goto have_git
echo.
echo Could not install Git automatically.
echo Install it from https://git-scm.com/download/win and run this file again.
pause
exit /b 1

:have_git
if exist "%TARGET%\.git" goto update
if exist "%TARGET%" goto occupied
echo Downloading the app to %TARGET% ...
"%GIT%" clone "%REPO%" "%TARGET%"
if errorlevel 1 goto failed
goto setup

:update
echo Updating the app in %TARGET% ...
"%GIT%" -C "%TARGET%" pull --ff-only
if errorlevel 1 goto failed

:setup
call "%TARGET%\local\setup.bat"
exit /b 0

:occupied
echo.
echo The folder %TARGET% already exists but was not created by this installer.
echo Rename or delete it, then run this file again.
pause
exit /b 1

:failed
echo.
echo Download failed - check the internet connection and run this file again.
pause
exit /b 1

:find_git
set "GIT="
for /f "delims=" %%G in ('where git 2^>nul') do if not defined GIT set "GIT=%%G"
if not defined GIT if exist "%ProgramFiles%\Git\cmd\git.exe" set "GIT=%ProgramFiles%\Git\cmd\git.exe"
exit /b 0
