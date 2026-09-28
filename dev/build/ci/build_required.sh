#!/bin/bash
# Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
#
# Tells if the full CI build (install + database upgrade + phpunit) is required
# for a list of changed files. Used by .travis.yml (to end a build early) and by
# .github/scripts/get_changed_php.sh (to skip the github job).
#
# Usage:  build_required.sh <file> [<file> ...]
#         git diff --name-only A B | build_required.sh
# Exit code: 0 = the build is required, 1 = it can be skipped.
#
# The phpunit suite scans the whole tree (CodingPhpTest reads every .php file
# below htdocs/, CodingSqlTest reads htdocs/install/*/*.sql and dev/initdemo/,
# LangTest reads htdocs/langs/*/main.lang), so we can not use a short allow list
# of "core" directories: almost any php or sql file may change the result. We
# list instead the files that can never change it.

set -euo pipefail

# Files that always require the build, even when the ignore list below matches
BUILD_FORCE_REGEX='^(\.travis\.yml|\.github/workflows/(gh-travis|ci-on-push|ci-on-pull_request)\.yml|\.github/scripts/get_changed_php\.sh|dev/build/ci/build_required\.sh|composer\.json|htdocs/langs/[a-zA-Z_]+/main\.lang|dev/initdemo/)'
# Files that can never change the result of the build
BUILD_IGNORE_REGEX='^(doc|dev|\.tx|\.phan|\.github|htdocs/langs)/|\.(md|txt|css|less|scss|png|jpg|jpeg|gif|svg|ico|webp|woff|woff2|ttf|eot)$|^(ChangeLog|COPYING|COPYRIGHT|DCO|\.editorconfig|\.gitignore|\.gitattributes|\.mailmap|\.pre-commit-config\.yaml|\.codeclimate\.yml|phpstan\.neon\.dist|pyproject\.toml)$'

# Read the file names from the command line or from the standard input
if [ "$#" -gt 0 ]; then
	changed_files=("$@")
else
	mapfile -t changed_files
fi

for changed_file in "${changed_files[@]}"; do
	[ -n "$changed_file" ] || continue
	if [[ "$changed_file" =~ $BUILD_FORCE_REGEX ]]; then
		echo "File $changed_file always requires the build"
		exit 0
	fi
	if [[ "$changed_file" =~ $BUILD_IGNORE_REGEX ]]; then
		continue
	fi
	echo "File $changed_file requires the build"
	exit 0
done

echo "No changed file can change the result of the build"
exit 1
