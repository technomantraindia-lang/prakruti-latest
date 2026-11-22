@echo off
title Launch Prakruti Application
echo Launching Prakruti Backend and Frontend...
start "Prakruti Backend" cmd /c "%~dp0start-backend.bat"
start "Prakruti Frontend" cmd /c "%~dp0start-frontend.bat"
echo Both services launched in separate windows.

