@echo off
REM ============================================================
REM  RESPALDO de SIGSM (Windows + XAMPP)
REM  Guarda en backup\copias\AAAA-MM-DD_HH-MM\ :
REM    - sigsm.sql        (toda la base de datos)
REM    - uploads.zip      (todos los PDF/archivos subidos)
REM  Uso: doble clic, o  backup\backup.bat  desde la carpeta del proyecto.
REM ============================================================
setlocal
set MYSQLBIN=C:\xampp\mysql\bin
set BASE=sigsm_prueba
set USUARIO=root
set CLAVE=

for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm"') do set FECHA=%%i
set RAIZ=%~dp0..
set DESTINO=%~dp0copias\%FECHA%
mkdir "%DESTINO%"

echo Respaldando la base de datos %BASE% ...
if "%CLAVE%"=="" (
  "%MYSQLBIN%\mysqldump.exe" -u %USUARIO% --routines --single-transaction %BASE% > "%DESTINO%\sigsm.sql"
) else (
  "%MYSQLBIN%\mysqldump.exe" -u %USUARIO% -p%CLAVE% --routines --single-transaction %BASE% > "%DESTINO%\sigsm.sql"
)
if errorlevel 1 (
  echo ERROR: no se pudo respaldar la base. Revise que MySQL este encendido en XAMPP.
  exit /b 1
)

echo Comprimiendo los archivos subidos ...
powershell -NoProfile -Command "Compress-Archive -Path '%RAIZ%\uploads\*' -DestinationPath '%DESTINO%\uploads.zip' -Force"
if errorlevel 1 (
  echo ERROR: no se pudo comprimir la carpeta uploads.
  exit /b 1
)

echo.
echo Respaldo listo en: %DESTINO%
dir "%DESTINO%"
endlocal
