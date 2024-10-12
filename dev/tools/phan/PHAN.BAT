@ECHO OFF
REM Usage (use from root of project):
REM  - Standard checks:
REM    PHAN.BAT
REM  - Extended checks:
REM    PHAN.BAT extended
REM  - Use fixer configuration:
REM    PHAN.BAT fix
REM
REM  Standard phan options can be added on the command line.

set MEMOPT=--memory-limit=4G
set CONFIG=--config-file
set CONFIG_FILE=dev/tools/phan/config.php
set FIX=
set USERARGS=
SET TWICE=--analyze-twice

rem Iterate through each argument
for %%i in (%*) do (
    if "%%i"=="--memory-limit" (
        set MEMOPT=""
    )
    if "%%i"=="extended" (
	set CONFIG="--config-file"
	set CONFIG_FILE=dev/tools/phan/config_extended.php
        goto :nextloop
    )
    if "%%i"=="fix" (
        set FIX="--automatic-fix"
	set CONFIG="--config-file"
	set CONFIG_FILE=dev/tools/phan/config_fixer.php
	set TWICE=
        goto :nextloop
    )
    if "%%i"=="--config-file" (
        set CONFIG=
        set CONFIG_FILE=
    )
    set "USERARGS=%USERARGS% %%i"

    :nextloop
    REM NEXTLOOP
)

../phan/vendor/bin/phan.bat %TWICE% %MEMOPT% %FIX% %CONFIG% %CONFIG_FILE% %USERARGS%
