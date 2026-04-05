@echo off
setlocal
call "%~dp0php-local.cmd" "%~dp0..\artisan" %*
