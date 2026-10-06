@echo off
rem ==========================================================================
rem  One-file installer for Windows - see docs\LOCAL-SETUP.md
rem  Double-click it: it downloads the app to C:\barq, installs what it needs
rem  and puts an icon on the desktop. Running it again updates the app.
rem ==========================================================================
setlocal
set "TARGET=C:\barq"
set "REPO=https://github.com/fmffoash/barq.git"

set "GIT="
for /f "delims=" %%G in ('where git 2^>nul') do if not defined GIT set "GIT=%%G"
if not defined GIT if exist "%ProgramFiles%\Git\cmd\git.exe" set "GIT=%ProgramFiles%\Git\cmd\git.exe"
if not defined GIT echo Installing Git - if Windows asks for permission, click Yes...
if not defined GIT winget install --id Git.Git -e --source winget --accept-package-agreements --accept-source-agreements
if not defined GIT if exist "%ProgramFiles%\Git\cmd\git.exe" set "GIT=%ProgramFiles%\Git\cmd\git.exe"
if not defined GIT goto nogit
if exist "%TARGET%\.git" goto update
if exist "%TARGET%" goto occupied

echo Downloading the app to %TARGET% ...
"%GIT%" clone "%REPO%" "%TARGET%"
if errorlevel 1 goto failed
call "%TARGET%\local\setup.bat"
exit /b

:update
rem One block: cmd reads all of it before running it, so git pull can safely
rem replace this very file (when it is the copy inside C:\barq\local).
(
  echo Updating the app in %TARGET% ...
  "%GIT%" -C "%TARGET%" pull --ff-only || goto failed
  call "%TARGET%\local\setup.bat"
  exit /b
)

:nogit
echo.
echo Could not install Git automatically.
echo Install it from https://git-scm.com/download/win and run this file again.
pause
exit /b 1

:occupied
echo.
echo The folder %TARGET% already exists but was not created by this installer.
echo Rename or delete it, then run this file again.
pause
exit /b 1

:failed
echo.
echo Could not download or update the app - see the message above.
echo Check the internet connection, then run this file again.
pause
exit /b 1
