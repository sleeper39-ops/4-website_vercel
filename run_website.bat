@echo off
rem ============================================================
rem  RO-2026 :: รันเว็บสดจากเครื่อง (ไม่ผ่าน GitHub cache)
rem  จำนวนคนออนไลน์จะอัปเดตภายใน ~1 นาที (Task Scheduler เขียนไฟล์ทุกนาที)
rem  -
rem  ตัวเองเปิด:   http://localhost:8080
rem  เพื่อนใน Radmin: http://%COMPUTERNAME%:8080 หรือ http://IPเครื่อง:8080
rem  (ถ้าเพื่อนเปิดไม่ได้ ตรวจ Windows Firewall อนุญาตพอร์ต 8080)
rem ============================================================
cd /d "%~dp0"
set "PHP=C:\Users\WD_Black\Documents\RO_OFFLINE_2026\1-start_database_run_laragon.exe\bin\php\php-8.3.28-Win32-vs16-x64\php.exe"

start "RO-PvP2026 Web" /min "%PHP%" -S 0.0.0.0:8080 -t "%~dp0"
timeout /t 1 /nobreak >nul
start "" http://localhost:8080/