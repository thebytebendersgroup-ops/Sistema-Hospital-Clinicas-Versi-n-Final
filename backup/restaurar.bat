@echo off
REM ============================================================
REM  RESTAURAR un respaldo de SIGSM (Windows + XAMPP)
REM  Uso:  backup\restaurar.bat  backup\copias\2026-10-27_10-30  [nombre_de_base]
REM  Si no se indica nombre de base, se restaura en  sigsm_verificacion  (copia de prueba,
REM  NO pisa la base real). Para restaurar de verdad, indique  sigsm_prueba.
REM ============================================================
setlocal
set MYSQLBIN=C:\xampp\mysql\bin
set USUARIO=root
set CLAVE=
set CARPETA=%~1
set BASE=%~2
if "%CARPETA%"=="" (
  echo Falta la carpeta del respaldo. Ejemplo: backup\restaurar.bat backup\copias\2026-10-27_10-30
  exit /b 1
)
if "%BASE%"=="" set BASE=sigsm_verificacion
set RAIZ=%~dp0..

echo Restaurando en la base %BASE% ...
"%MYSQLBIN%\mysql.exe" -u %USUARIO% -e "DROP DATABASE IF EXISTS %BASE%; CREATE DATABASE %BASE% CHARACTER SET utf8mb4;"
if errorlevel 1 ( echo ERROR: no se pudo crear la base. & exit /b 1 )
"%MYSQLBIN%\mysql.exe" -u %USUARIO% %BASE% < "%CARPETA%\sigsm.sql"
if errorlevel 1 ( echo ERROR: no se pudo importar sigsm.sql. & exit /b 1 )

if /I "%BASE%"=="sigsm_prueba" (
  echo Restaurando los archivos subidos en uploads\ ...
  powershell -NoProfile -Command "Expand-Archive -Path '%CARPETA%\uploads.zip' -DestinationPath '%RAIZ%\uploads' -Force"
)

echo.
echo Controles de la recuperacion (compare con la base original):
"%MYSQLBIN%\mysql.exe" -u %USUARIO% %BASE% < "%~dp0verificar_recuperacion.sql"
endlocal
