@echo off
rem Creates the app login, or changes its password (same email = same account).
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0create-admin.ps1" & pause
