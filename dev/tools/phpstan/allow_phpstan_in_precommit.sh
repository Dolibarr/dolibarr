#!/bin/bash
# Wrapper to run 'PHPStan' from pre-commit hook
# This is very slow so not enabled by default
# To enable it, create a file ~/.run-phpstan
# To disable it, remove this file ~/.run-phpstan

echo "Running PHPStan on files ~/vendor/bin/phpstan --level=9 -v analyze -a dev/build/phpstan/bootstrap.php $@"

# Test presence of file
if [ ! -f ~/.run-phpstan ]; then
	echo "Skipping PHPStan (file ~/.run-phpstan missing)"
	exit 0
fi

if [ ! -f ~/vendor/bin/phpstan ]; then
	echo "Skipping PHPStan (file ~/vendor/bin/phpstan missing)"
	exit 0
fi

~/vendor/bin/phpstan --level=9 -v analyze -a dev/build/phpstan/bootstrap.php $@

exit $?
