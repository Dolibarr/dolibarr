@echo off
REM --------------------------------------------------------
REM This script start the Apache and Mysql DoliWamp services
REM --------------------------------------------------------

echo ---- Execute stopdoliwamp.bat >> doliwamp.log 2>>&1

echo NET STOP doliwampapache >> doliwamp.log 2>>&1
NET STOP doliwampapache
echo NET STOP doliwampmysqld >> doliwamp.log 2>>&1
NET STOP doliwampmysqld 

echo Please wait...
echo ---- End script >> doliwamp.log 2>>&1

sleep 1