@echo off
REM ============================================================
REM  Start stock-control Laravel backend (PHP 8.3 portable)
REM  Double-click file ini untuk menyalakan server.
REM  JANGAN pakai "php artisan serve" biasa (itu XAMPP PHP 8.2 -> error).
REM ============================================================

set "PHP_EXE=C:\Users\Bonecom tricom\Downloads\php-8.3.31-Win32-vs16-x64\php.exe"

cd /d "%~dp0"

if not exist "%PHP_EXE%" (
    echo [ERROR] PHP 8.3 tidak ditemukan di:
    echo         %PHP_EXE%
    echo Periksa kembali lokasi PHP portable.
    pause
    exit /b 1
)

echo Pastikan MySQL (XAMPP) sudah jalan.
echo Menjalankan server di http://127.0.0.1:8009 ...
echo Tekan Ctrl+C untuk berhenti.
echo.

"%PHP_EXE%" artisan serve --host=127.0.0.1 --port=8009

pause
