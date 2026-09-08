#!/bin/bash
# Copyright (C) 2026       Frédéric France         <frederic.france@free.fr>

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

# phpstan.neon.dist only analyzes htdocs/ and scripts/: keep only files under these dirs so a commit
# that touches only out-of-scope files (test/phpunit/, dev/, doc/, ...) does not make phpstan error
# out with "No files found to analyse".
filtered=()
for f in "$@"; do
	case "$f" in
		htdocs/*|scripts/*) filtered+=("$f") ;;
	esac
done

if [ ${#filtered[@]} -eq 0 ]; then
	echo "Skipping PHPStan (no file in scope: htdocs/ or scripts/)"
	exit 0
fi

~/vendor/bin/phpstan --level=9 -v analyze -a dev/build/phpstan/bootstrap.php "${filtered[@]}"

exit $?
