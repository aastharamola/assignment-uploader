@echo off
setlocal

REM ============================================
REM ASSIGNMENT UPLOADER - ONE COMMAND START
REM ============================================

echo.
echo =======================================
echo   Assignment Uploader - Start
echo =======================================
echo.

REM Ensure script runs from project root
cd /d "%~dp0"

if not exist "frontend\index.html" (
    echo ERROR: Project files not found.
    echo Run this file from the assignment-uploader folder.
    pause
    exit /b 1
)

where php >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP is not available in PATH.
    echo Install PHP or add it to PATH, then run again.
    pause
    exit /b 1
)

if not exist "backend\uploads" (
    echo Creating uploads directory...
    mkdir "backend\uploads"
)

set "APP_URL=http://localhost:8000/assignment-uploader/frontend/index.html"

echo.
echo IMPORTANT: Start MySQL from XAMPP/WAMP before login/register.
echo.
echo Opening app URL: %APP_URL%
start "" "%APP_URL%"

echo.
echo Starting PHP server on http://localhost:8000
echo Press Ctrl + C to stop.
echo.

REM Serve from parent so /assignment-uploader/* paths resolve correctly
php -S localhost:8000 -t ..

endlocal