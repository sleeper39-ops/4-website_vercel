@echo off
rem ============================================================
rem  RO-2026 :: อัปเดตสถิติลง data/server.json แล้ว push ขึ้น GitHub
rem  ใช้กับ Task Scheduler ทุก 1 นาที เพื่อให้ค่าคนออนไลน์สด
rem ============================================================
setlocal
cd /d "%~dp0"

set "PHP=C:\Users\WD_Black\Documents\RO_OFFLINE_2026\1-start_database_run_laragon.exe\bin\php\php-8.3.28-Win32-vs16-x64\php.exe"
set "GIT=C:\Program Files\Git\cmd\git.exe"

echo [1/3] ดึงสถิติจากฐานข้อมูล
"%PHP%" fetch_online.php
if errorlevel 1 (
    echo [ERR] ดึงสถิติไม่สำเร็จ ตรวจว่า MariaDB รันอยู่ โดยเริ่มจาก 1-start_database_run_laragon.exe ก่อน
    exit /b 1
)

echo [2/3] Commit + Push เฉพาะตอนข้อมูลเปลี่ยน
"%GIT%" add data/server.json
"%GIT%" diff --cached --quiet
if not errorlevel 1 goto nopush
"%GIT%" commit -m "chore: refresh server stats" 1>nul 2>nul
if errorlevel 1 goto errpush
"%GIT%" push origin main
if errorlevel 1 goto errpush

goto nopush

:errpush
echo [ERR] Push ล้มเหลว ตรวจ git remote และการล็อกอิน
exit /b 1

:nopush
echo [3/3] เสร็จเรียบร้อย สถิติอัปเดตแล้ว %date% %time%
endlocal