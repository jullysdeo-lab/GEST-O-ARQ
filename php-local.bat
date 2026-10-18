@echo off
rem Roda o PHP com as extensoes que o sistema usa (SQLite e mbstring),
rem sem precisar mexer no php.ini do seu computador.
for %%i in (php.exe) do set "PHPDIR=%%~dp$PATH:i"
if "%PHPDIR%"=="" (
  echo PHP nao encontrado. Instale o PHP 8.2 ou mais novo.
  exit /b 1
)
php -d extension_dir="%PHPDIR%ext" -d extension=pdo_sqlite -d extension=mbstring %*
