@ECHO OFF
:REPEAT_EXEC
FOR %%f IN (*.pdf) DO date.exe +"%%Hh%%M:%%S" & pdfp %%f & del %%f /q

sleep.exe 2

GOTO REPEAT_EXEC