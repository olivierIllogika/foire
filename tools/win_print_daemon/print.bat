date.exe +"%%Hh%%M:%%S" >> out.txt
echo %1 >> out.txt
pdfp %1 >> out.txt
del %1 /q
date.exe +"%%Hh%%M:%%S"
echo %1
