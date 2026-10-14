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

output=$(~/vendor/bin/phpstan --level=9 -v analyze -a dev/build/phpstan/bootstrap.php "${filtered[@]}" 2>&1)
result=$?
echo "$output"

# Even after the htdocs/scripts filter above, a batch can still end up containing only files
# excluded by phpstan.neon.dist's own excludePaths (e.g. a vendored library under htdocs/includes/*
# that is fully out of scope for analysis). PHPStan then errors with "No files found to analyse"
# instead of just reporting zero errors, so treat that specific case as a pass too.
if [ $result -ne 0 ] && echo "$output" | grep -q "No files found to analyse"; then
	echo "Skipping PHPStan (all files in this batch are excluded by phpstan.neon.dist)"
	exit 0
fi

exit $result
