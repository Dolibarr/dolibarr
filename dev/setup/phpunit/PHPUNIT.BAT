REM SAMPLE WINDOWS SCRIPT TO START TESTS
REM
REM CURRENTLY THIS SCRIPT MUST BE LOCATED AT THE ROOT OF THE PROJECT.
REM A copy of phpunit-9.5.phar is required
SET OPEN_BASEDIR=%~dp0
c:\wamp64\bin\php\php7.4.33\php -dopen_basedir=%OPEN_BASEDIR%  .\phpunit-9.5.phar -d memory_limit=-1 -c test\phpunit\phpunittest.xml test\phpunit\AllTests.php
