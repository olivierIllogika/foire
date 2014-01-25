@ECHO OFF
:REPEAT_EXEC
FOR %%f IN (in\*.pdf) DO @CALL print.bat %%f

sleep.exe 2

GOTO REPEAT_EXEC