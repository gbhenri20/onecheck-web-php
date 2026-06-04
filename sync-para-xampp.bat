@echo off
echo Sincronizando OneCheck para o XAMPP...
robocopy "%~dp0" "C:\xampp\htdocs\onecheck" /E /XD .git /NFL /NDL /NJH /NJS
echo.
echo Pronto! Acesse: http://localhost/onecheck/public/login.php
pause
