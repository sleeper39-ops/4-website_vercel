@echo off
rem =====================================================================
rem  RO-2026 :: อัปเดตสถิติลง data/server.json แล้ว push ขึ้น GitHub
rem  (Vercel จะ deploy ใหม่อัตโนมัติหลัง push ผ่าน)
rem  ใช้กับ Task Scheduler เพื่อให้อัปเดตสถิติอัตโนมัติ เช่น ทุก 10 นาที
rem =====================================================================
setlocal
cd /d "%~dp0"

set "PHP=C:\Users\WD_Black\Documents\RO_OFFLINE_2026\1-start_database_run_laragon.exe\bin\php\php-8.3.28-Win32-vs16-x64\php.exe"

echo [1/3] ดึงสถิติจากฐานข้อมูล...
"%PHP%" fetch_online.php
if errorlevel 1 (
    echo [ERR] ดึงสถิติไม่สำเร็จ - ตรวจว่า MariaDB รันอยู่ (เริ่มจาก 1-start_database_run_laragon.exe)
    exit /b 1
)

echo [2/3] Commit + Push ขึ้น GitHub...
git add -A
git commit -m "chore: refresh server stats (%date%)" 1>nul 2>nul
git push
if errorlevel 1 (
    echo [ERR] Push ล้มเหลว - ตรวจ git remote / ตรวจ login git
    exit /b 1
)

echo [3/3] เสร็จเรียบร้อย - สถิติอัปเดตแล้ว
endlocal