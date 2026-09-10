@echo off
title PHP Local Server - SiPoin
cd /d "%~dp0"
echo Server sedang berjalan di http://localhost:8080
php -S localhost:8080 api/index.php
pause
