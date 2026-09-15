@echo off
rem ============================================================
rem  RO-2026 :: Realtime Website & Database Sync
rem  - Pulls stats from MySQL (fetch_online.php)
rem  - Commits & pushes to GitHub/Vercel whenever:
rem    1) Online players / server status changes
rem    2) index.html / website content is modified
rem    3) Heartbeat (every 5 minutes) to keep timestamps live
rem ============================================================
setlocal
cd /d "%~dp0"

set "PHP=D:\RO_PvP_Class2\1-start_database_run_laragon.exe\bin\php\php-8.3.28-Win32-vs16-x64\php.exe"
set "GIT=C:\Program Files\Git\cmd\git.exe"
set "FLAG=data\flag_changed"
set "LAST=%~dp0.lastpush"

echo [1/3] Pulling stats from database...
"%PHP%" fetch_online.php | findstr /r "^DATA_" >nul
if errorlevel 1 (
    echo [ERR] Fetch failed - make sure MariaDB is running in Laragon.
    exit /b 1
)

rem Stage all changes (including index.html, data/server.json, gallery, etc.)
"%GIT%" add -A

rem Check if there are staged changes
"%GIT%" diff --cached --quiet
set "HAS_CHANGES=%errorlevel%"

set "PUSH=0"
if "%HAS_CHANGES%"=="1" (
    set "PUSH=1"
) else (
    rem Heartbeat: push every 5 minutes even if stats identical
    if exist "%LAST%" (
        for /f %%a in ('powershell -NoProfile -Command "$f=Get-Item '%LAST%'; if(((Get-Date)-$f.LastWriteTime).TotalMinutes -ge 5){'1'}else{'0'}"') do set PUSH=%%a
    ) else (
        set PUSH=1
    )
)

if "%PUSH%"=="0" (
    echo [2/3] Data and website unchanged - skipping push
    goto done
)

echo [2/3] Syncing with GitHub / Vercel...
rem Rebase any remote changes first to prevent rejections
"%GIT%" pull --rebase origin main >nul 2>&1

rem Re-add in case rebase touched anything
"%GIT%" add -A
"%GIT%" diff --cached --quiet
if not errorlevel 1 (
    echo [2/3] No changes after rebase - done
    goto done
)

"%GIT%" commit -m "chore: realtime stats & website sync [%date% %time%]" >nul 2>&1
if errorlevel 1 goto errpush

"%GIT%" push origin main
if errorlevel 1 goto errpush

type nul > "%LAST%"
if exist "%FLAG%" del /f /q "%FLAG%" >nul 2>&1
goto done

:errpush
echo [ERR] Git push failed. Please check network or credentials.
exit /b 1

:done
echo [3/3] Done. Website synced at %date% %time%
endlocal
