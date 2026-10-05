@echo off
rem Gets the latest code and rebuilds - close the start.bat window first
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0update.ps1"
pause
