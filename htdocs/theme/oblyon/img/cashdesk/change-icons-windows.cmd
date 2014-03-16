REM # 	Dolibarr | Change original Cashdesk icons for Oblyon theme
REM 		Author: Nicolas Rivera 	2014
@echo off
 title Changement des icones Cashdesk Dolibarr
  color 07
cls
 ::initialisation variables
  set target_path=..\..\..\..\cashdesk\img
  set old_folder=..\..\..\..\cashdesk\img_old
  
if not exist %target_path% echo Le dossier  Cashdesk est introuvable ! &&goto :end
if exist %old_folder% echo Les icones originales ont deja fait l'objet d'une modification... &&goto :copy 
:backup
xcopy %target_path% %old_folder% /Y /I /Q
if errorlevel 0 del /Q %target_path%
echo Backup effectue.
echo.
:copy
xcopy *.png %target_path% /Y /Q
if exist index.php xcopy index.php %target_path% /Y /Q
if errorlevel 0 echo Remplacement des icones effectue avec succes !
echo.
echo.
echo Femeture automatique du programme...

:end
ping 127.0.0.1 -n 7 > NUL 
exit