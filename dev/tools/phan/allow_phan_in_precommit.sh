#!/bin/bash
# Wrapper to run 'Phan' from pre-commit hook
# This is very slow so not enabled by default
# To enable it, create a file ~/.run-phan
# To disable it, remove this file ~/.run-phan


echo "Running Phan on files ~/vendor/bin/phan $@"

# Test presence of file
if [ ! -f ~/.run-phan ]; then
	echo "Skipping Phan (file ~/.run-phan missing)"
	exit 0
fi

if [ ! -f ~/vendor/bin/phan ]; then
	echo "Skipping Phan (file ~/vendor/bin/phpan missing)"
	exit 0
fi

~/vendor/bin/phan $@

exit $?
