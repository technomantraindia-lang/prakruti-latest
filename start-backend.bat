@echo off
title Prakruti Backend Server (Port 8000)
cd /d "%~dp0backend"
echo Starting Prakruti Backend API on http://127.0.0.1:8000 ...
"C:\xampp\php\php.exe" artisan serve --host=127.0.0.1 --port=8000
pause

