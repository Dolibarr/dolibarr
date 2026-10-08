#!/bin/bash
# Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>

#
# Script to add codespell exceptions to the ignores lines file.
#
# The file is named '...-lines-ignore' to make TAB expansion on the cli easier.
#
# The line in the ignore file must match the line in the source
# exactly.
#
# To clean up or create the ignored lines file, just do
#   ```shell
#   echo > dev/tools/codespell/codespell-lines-ignore.txt
#   ```
# and then execute this script.
#
# author: https://github.com/mdeweerd

#
# :warning:
#
# This script only works properly if codespell is installed for your CLI.
# As the configuration is in pyproject.toml, you also need tomli.
#
# ```shell
# python -m pip install codespell tomli
# # or
# pip install codespell tomli
# ```

codespell_ignore_file=dev/tools/codespell/codespell-lines-ignore.txt
if [ -z "${0##*.sh}" ] ; then
	# Suppose running from inside script
	# Get real path
	script=$(realpath "$(test -L "$0" && readlink "$0" || echo "$0")")
	PROJECT_ROOT=$(realpath "${script}")

	while [ "${PROJECT_ROOT}" != "/" ] ; do
		[ -r "${PROJECT_ROOT}/${codespell_ignore_file}" ] && break
		PROJECT_ROOT=$(dirname "${PROJECT_ROOT}")
	done
	if [ "${PROJECT_ROOT}" == "/" ] ; then
		echo "Project root not found from '${script}'"
		exit 1
	fi
	codespell_ignore_file=${PROJECT_ROOT}/${codespell_ignore_file}
fi


# Make sure we are at the root of the project
[ -r "${codespell_ignore_file}" ] || { echo "${codespell_ignore_file} not found" ; exit 1 ; }
# Then:
#   - Run codespell;
#   - Identify files that have fixes;
#   - Limit to files under git control;
#   - Run codespell on selected files;
#   - For each line, create a grep command to find the lines;
#   - Execute that command by evaluation
codespell . \
	| sed -n -E 's@^([^:]+):.*@\1@p' \
	| xargs -r git ls-files -- \
	| xargs -r codespell -- \
	| sed -n -E 's@^([^:]+):[[:digit:]]+:[[:space:]](\S+)[[:space:]].*@grep -P '\''\\b\2\\b'\'' -- "\1" >> '"${codespell_ignore_file}"'@p' \
	| while read -r line ; do eval "$line" ; done

# Finally, sort and remove duplicates to make merges easier.
sort -u -o "${codespell_ignore_file}"{,}
