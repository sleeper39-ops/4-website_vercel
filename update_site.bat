@echo off
rem ============================================================
rem  RO-2026 :: อัปเดตสถิติลง data/server.json แล้ว push GitHub
rem  ใช้กับ Task Scheduler ทุก 1 นาที เพื่อให้ค่าคนออนไลน์สด
rem  -
rem  Push จำกัดเพื่อไม่ให้ GitHub Pages ชน build limit (~10/ชม.):
rem   - ข้อมูลจริงเปลี่ยน (online/accounts/..)  -> push ทันที
rem   - ข้อมูลเหมือนเดิมแต่ผ่านไป >= 10 นาที    -> heartbeat push (อัปเดต updated_at)
rem ============================================================
setlocal
cd /d "%~dp0"

set "PHP=C:\Users\WD_Black\Documents\RO_OFFLINE_2026\1-start_database_run_laragon.exe\bin\php\php-8.3.28-Win32-vs16-x64\php.exe"
set "GIT=C:\Program Files\Git\cmd\git.exe"
set "FLAG=data\flag_changed"
set "LAST=%~dp0.lastpush"

echo [1/3] ดึงสถิติจากฐานข้อมูล
"%PHP%" fetch_online.php | findstr /r "^DATA_" >nul
if errorlevel 1 (
    echo [ERR] ดึงสถิติไม่สำเร็จ ตรวจว่า MariaDB รันอยู่ โดยเริ่มจาก 1-start_database_run_laragon.exe ก่อน
    exit /b 1
)

rem ===== ตัดสินใจว่าจะ push หรือไม่ =====
set "PUSH=0"
rem 1) flag_changed อายุ < 3 นาที  -> ข้อมูลจริงเพิ่งเปลี่ยน
if exist "%FLAG%" (
    for /f %%a in ('powershell -NoProfile -Command "$f=Get-Item '%FLAG%'; if(((Get-Date)-$f.LastWriteTime).TotalMinutes -lt 3){'1'}else{'0'}"') do set PUSH=%%a
)
rem 2) heartbeat: นับจาก push ครั้งล่าสุด >= 10 นาที
if "%PUSH%"=="0" (
    if exist "%LAST%" (
        for /f %%a in ('powershell -NoProfile -Command "$f=Get-Item '%LAST%'; if(((Get-Date)-$f.LastWriteTime).TotalMinutes -ge 10){'1'}else{'0'}"') do set PUSH=%%a
    ) else (
        set PUSH=1
    )
)

if "%PUSH%"=="0" (
    echo [2/3] ข้อมูลไม่เปลี่ยนและยังไม่ถึงรอบ heartbeat - ข้าม push
    goto done
)

echo [2/3] Commit + Push ไปยัง GitHub
"%GIT%" add data/server.json
"%GIT%" diff --cached --quiet
if not errorlevel 1 goto done
"%GIT%" commit -m "chore: refresh server stats" 1>nul 2>nul
if errorlevel 1 goto errpush
"%GIT%" push origin main
if errorlevel 1 goto errpush

rem บันทึกเวลาที่ push สำเร็จ
type nul > "%LAST%"
goto done

:errpush
echo [ERR] Push ล้มเหลว ตรวจ git remote และการล็อกอิน
exit /b 1

:done
echo [3/3] เสร็จเรียบร้อย สถิติอัปเดตแล้ว %date% %time%
endlocal