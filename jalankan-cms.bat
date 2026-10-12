@echo off
REM ============================================
REM  CMS TSM Blitar - Server Launcher
REM  Jalankan ini (dobel-klik) sebelum akses web.
REM ============================================
cd /d "D:\ApplicationWeb\cms-tsm"

echo [1/4] Cek MySQL (XAMPP)...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I "mysqld.exe" >NUL
if %ERRORLEVEL% NEQ 0 (
    echo     MySQL mati, nyalakan dari XAMPP Control Panel dulu.
    start "" "C:\xampp\xampp-control.exe"
    timeout /t 10 >NUL
) else (
    echo     MySQL OK
)

echo [2/4] Cek server PHP di port 8000...
netstat -an | find ":8000" | find "LISTENING" >NUL
if %ERRORLEVEL% NEQ 0 (
    echo     Server mati, menyalakan...
    start "" /B cmd /C "C:\php83\php.exe artisan serve --host=0.0.0.0 --port=8000"
    timeout /t 8 >NUL
) else (
    echo     Server sudah jalan
)

echo [3/4] Selesai.
echo.
echo  ================================================
echo   CMS TSM Blitar sudah jalan!
echo  ================================================
echo.
echo   Dari laptop ini : http://127.0.0.1:8000
echo   Dari HP (WiFi yang sama): http://192.168.1.6:8000
echo.
echo   Admin: http://127.0.0.1:8000/admin
echo   Email : admin@tsm-blitar.sch.id
echo   Sandi : admin12345
echo.
echo   Catatan: Jangan tutup jendela ini selama web dipakai.
echo.
pause
