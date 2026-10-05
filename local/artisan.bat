@echo off
rem Runs "php artisan ..." with the app's private PHP copy (.runtime), from any window.
rem Example (PowerShell in C:\barq):  .\local\artisan.bat barq:doctor
setlocal
set "ROOT=%~dp0.."
if exist "%ROOT%\.runtime\php\php.exe" set "PATH=%ROOT%\.runtime\php;%PATH%"
pushd "%ROOT%"
php artisan %*
set "CODE=%ERRORLEVEL%"
popd
exit /b %CODE%
