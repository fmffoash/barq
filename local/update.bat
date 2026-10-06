@echo off
rem Gets the latest code and rebuilds - close the app window first
rem (one line: git pull may replace this very file while it runs)
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0update.ps1" & pause
