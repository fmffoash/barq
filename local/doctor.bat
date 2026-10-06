@echo off
rem Health check - shows OK / WARN / FAIL for everything the app needs, with the fix next to each problem.
call "%~dp0artisan.bat" barq:doctor
pause
