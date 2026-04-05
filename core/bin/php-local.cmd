@echo off
setlocal
set "PHP_EXE=C:\Users\uscap\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
set "PHP_INI=%~dp0..\dev.php.ini"
"%PHP_EXE%" -c "%PHP_INI%" %*
