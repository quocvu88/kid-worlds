@echo off
title Kids World Web CMS & API Server
cd /d "%~dp0"
echo ========================================================
echo   KIDS WORLD - TOPIC PACKS CMS ^& CONTENT SERVER
echo ========================================================
echo.
echo Server dang khoi dong tai:
echo   - Local PC:             http://localhost:8000
echo   - Android Studio:       http://10.0.2.2:8000
echo   - Wi-Fi Device:         http://192.168.2.6:8000
echo.
echo Nhan Ctrl+C de dung server.
echo.
php -S 0.0.0.0:8000 -t public
pause
