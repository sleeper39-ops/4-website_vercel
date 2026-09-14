@echo off
rem ============================================================
rem  RO-2026 :: Update data/server.json then push to GitHub.
rem  Used by Task Scheduler (ROPvP2026-SiteStats) periodically
rem  to keep the site stats fresh.
rem  Push policy (fast refresh):
rem   - real data changed within the last 1 minute -> push now
rem   - otherwise heartbeat push every 15 minutes (freshen timestamp)
rem ============================================================
setlocal
cd /d "%~dp0"

set "PHP=D:\RO_PvP_Class2\1-start_database_run_laragon.exe\bin\php\php-8.3.28-Win32-vs16-x64\php.exe"
set "GIT=C:\Program Files\Git\cmd\git.exe"
set "FLAG=data\flag_changed"
set "LAST=%~dp0.lastpush"

echo [1/3] Pull stats from database
"%PHP%" fetch_online.php | findstr /r "^DATA_" >nul
if errorlevel 1 (
    echo [ERR] Fetch failed - make sure MariaDB is running: start 1-start_database_run_laragon.exe first
    exit /b 1
)

rem ===== Decide whether to push or not =====
set "PUSH=0"
rem 1) real data changed within the last 1 minute -> push immediately
if exist "%FLAG%" (
    for /f %%a in ('powershell -NoProfile -Command "$f=Get-Item '%FLAG%'; if(((Get-Date)-$f.LastWriteTime).TotalMinutes -lt 1){'1'}else{'0'}"') do set PUSH=%%a
)
rem 2) heartbeat: last successful push >= 15 minutes ago
if "%PUSH%"=="0" (
    if exist "%LAST%" (
        for /f %%a in ('powershell -NoProfile -Command "$f=Get-Item '%LAST%'; if(((Get-Date)-$f.LastWriteTime).TotalMinutes -ge 15){'1'}else{'0'}"') do set PUSH=%%a
    ) else (
        set PUSH=1
    )
)

if "%PUSH%"=="0" (
    echo [2/3] Data unchanged and not yet heartbeat yet - skip push
    goto done
)

echo [2/3] Commit + Push to GitHub
"%GIT%" add data/server.json data/gallery.json gallery
"%GIT%" diff --cached --quiet
if not errorlevel 1 goto done
"%GIT%" commit -m "chore: refresh server stats" 1>nul 2>nul
if errorlevel 1 goto errpush
"%GIT%" push origin main
if errorlevel 1 goto errpush

rem record last successful push time
type nul > "%LAST%"
goto done

:errpush
echo [ERR] Push failed - check git remote and login
exit /b 1

:done
echo [3/3] Done. Stats refreshed at %date% %time%
endlocal
