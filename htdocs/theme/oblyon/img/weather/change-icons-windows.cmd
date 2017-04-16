REM # 	Dolibarr | Change original weather icons for Oblyon theme
REM 		Author: Nicolas Rivera 	2014
@echo off
 title Changement des icones meteo Dolibarr
  color 07
cls
 ::initialisation variables
  set target_path=..\..\..\common\weather
  set old_folder=..\..\..\common\weather_old
  
if not exist %target_path% echo Le dossier de destination est introuvable ! &&goto :end
if exist %old_folder% echo Les icones originales ont deja fait l'objet d'une modification... &&goto :copy 
:backup
xcopy %target_path% %old_folder% /Y /I /Q
echo Backup effectue.
echo.
:copy
xcopy *.png %target_path% /Y /Q
if errorlevel 0 echo Remplacement des icones effectue avec succes !
echo.
echo.
echo Femeture automatique du programme...

:end
ping 127.0.0.1 -n 7 > NUL 
exit