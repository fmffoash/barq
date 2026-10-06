@echo off
rem Starts the app and opens it in the browser - closing this window stops the app
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0start.ps1"
if errorlevel 1 pause
