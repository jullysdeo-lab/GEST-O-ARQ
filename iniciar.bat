@echo off
cd /d "%~dp0"

if not exist "app\config.php" copy "app\config.example.php" "app\config.php" >nul
if exist "storage\database.sqlite" goto servidor

echo Primeira vez: vamos criar o banco e o seu usuario.
set /p ESCRITORIO=Nome do escritorio:
set /p NOME=Seu nome:
set /p EMAIL=Seu e-mail:
set /p SENHA=Senha (minimo 8 caracteres):
call php-local.bat install.php --escritorio="%ESCRITORIO%" --nome="%NOME%" --email="%EMAIL%" --senha="%SENHA%" --exemplo
if errorlevel 1 goto falhou

:servidor
call php-local.bat migrate.php
echo.
echo Sistema rodando em http://localhost:8000
echo Feche esta janela para parar.
start "" http://localhost:8000
call php-local.bat -S localhost:8000 -t public public/index.php
goto :eof

:falhou
if exist "storage\database.sqlite" del "storage\database.sqlite"
pause
exit /b 1
