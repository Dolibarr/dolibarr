@echo off
REM --------------------------------------------------------
REM This script start the Apache and Mysql DoliWamp services
REM --------------------------------------------------------

echo ---- Execute startdoliwamp.bat >> doliwamp.log 2>>&1

echo NET START doliwampapache >> doliwamp.log 2>>&1
NET START doliwampapache >> doliwamp.log 2>>&1
echo NET START doliwampmysqld >> doliwamp.log 2>>&1
NET START doliwampmysqld >> doliwamp.log 2>>&1

echo Please wait...
echo ---- End script >> doliwamp.log 2>>&1

sleep 1
