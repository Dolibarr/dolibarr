@echo off
REM --------------------------------------------------------
REM This script start the Apache and Mysql DoliWamp services
REM --------------------------------------------------------

echo ---- Execute startdoliwamp.bat >> doliwamp.log 2>>&1

echo NET START doliwampapache >> doliwamp.log 2>>&1
NET START doliwampapache >> doliwamp.log 2>>&1
echo NET START doliwampmysqld >> doliwamp.log 2>>&1
NET START doliwampmysqld >> doliwamp.log 2>>&1

REM You can also check logs into c:/dolibarr/logs if start fails

echo Please wait...
echo ---- End script >> doliwamp.log 2>>&1

REM sleep is not a Windows commande
REM sleep 2
ping 127.0.0.1 -n 2 -w 1000 > nul
