@echo off
title Prakruti Frontend Storefront (Port 5173)
cd /d "%~dp0frontend"
echo Starting Prakruti Frontend on http://127.0.0.1:5173 ...
call npm run dev:direct
pause

