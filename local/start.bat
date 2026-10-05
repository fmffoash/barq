@echo off
rem Starts the app and opens it in the browser - keep this window open while you work
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0start.ps1"
if errorlevel 1 pause
