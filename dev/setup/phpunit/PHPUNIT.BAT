REM @ECHO OFF

SET OPEN_BASEDIR=%~dp0
SET DD_TRACE_CLI_ENABLED=1
SET DD_TRACE_CLI_LOG_FILE=%~dp0/trace.log
SET PHP=c:\wamp64\bin\php\php7.4.33\php

REM c:\wamp64\bin\php\php7.4.33\php -dopen_basedir=%OPEN_BASEDIR%  .\phpunit-9.5.phar -d memory_limit=-1 -c test\phpunit\phpunittest.xml test\phpunit\AllTests.php --filter FilesLibTest --migrate-configuration
REM Example: phpunit.bat test/phpunit/FilesLibTest.php --filter FilesLibTest::testDolDirMove
REM

REM The DOMAIN/Port value should be the same as in htdocs/conf/conf.php
REM (This is only needed if did not set up another server locally
REM  for your test installation database).
SET PHPSERVER_DOMAIN_PORT=127.0.0.1:8080
SET PHPSERVER_LOG=%~dp0/php_serv.log

echo if (class_exists('PHPUnit\Framework\TestSuite')) { $dolibarr_main_url_root='http://%PHPSERVER_DOMAIN_PORT%'; } >> %~dp0\htdocs\conf\conf.php
GOTO :START

REM The error handler
:ERROR_HANDLER
echo An error occurred.  Stop php server
taskkill /F /PID %php_pid%
exit /b 1

:START

start /B php -S %PHPSERVER_DOMAIN_PORT% -t htdocs > %PHPSERVER_LOG% 2>&1
for /F "tokens=2 delims=," %%i in ('tasklist /FI "IMAGENAME eq php.exe" /FO CSV /NH') do (
    set "php_pid=%%~i"
    goto :FOUND_PID
)

:FOUND_PID
echo "Server PID: %php_pid%"
REM curl "http://%PHPSERVER_DOMAIN_PORT%"


SET MEMOPT=-d memory-limit=-1
SET CONFOPT=-c test\phpunit\phpunittest.xml
SET TESTS=test\phpunit\AllTests.php

setlocal enabledelayedexpansion

for %%i in (%*) do (
    set "f=%%i"
    if "!f:~0,12!"=="memory-limit" (
        SET MEMOPT=""
        goto :continue
    )
    if "%%i"=="-c" (
        SET CONFOPT=""
        goto :continue
    )
    if "!f:~-4!"==".php" (
        set TESTS=""
        goto :continue
    )
    :continue
        REM NEEDED FOR CONTINUE

)

%PHP% -d display_errors=on -dopen_basedir=%OPEN_BASEDIR% .\phpunit-9.5.phar %MEMOPT% %CONFOPT% %TESTS% %*

REM c:\wamp64\bin\php\php7.4.33\php -dopen_basedir=%OPEN_BASEDIR%  .\phpunit-9.5.phar -d memory_limit=-1 -c test\phpunit\phpunittest.xml test\phpunit\SecurityTest.php

REM c:\wamp64\bin\php\php7.4.33\php -dopen_basedir=%OPEN_BASEDIR%  .\phpunit-9.5.phar -d memory_limit=-1 -c test\phpunit\phpunittest.xml
REM c:\wamp64\bin\php\php7.4.33\php -dopen_basedir=%OPEN_BASEDIR%  .\phpunit-9.5.phar -d memory_limit=-1 test\phpunit\AllTests.php --filter FilesLibTest


taskkill /F /PID %php_pid%
