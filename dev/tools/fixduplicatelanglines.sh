#!/bin/bash
# Recursively deduplicate file lines on a per file basis
# Useful to deduplicate language files
#
# Needs awk 4.0 for the inplace fixing command
#
# Copyright (C) 2016		Raphaël Doursenaud					<rdoursenaud@gpcsolutions.fr>
# Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>

exit_code=0

# Check arguments
if [ "$1" != "list" ] && [ "$1" != "fix" ]
then
	echo "Find exact duplicated lines into file (not cross file checking)"
	echo "Usage: $(basename "$0") [list|fix]"
	exit_code=1
fi

ACTION=$1

# To detect
if [ "${ACTION}" = "list" ] || [ "${ACTION}" = "fix" ]
then
	echo "Search duplicate lines for lang en_US"
	echo ""
	for file in htdocs/langs/en_US/*.lang
	do
		if [ "$(sort "$file" | grep -v -P '^#?$' | uniq -d | wc -l)" -gt 0 ]
		then
			sort "$file" | grep -v -P '^#?$' | uniq -d | awk '$0="'"$file"':"$0'
			exit_code=1
		fi
	done
fi

# To fix
if [ "${ACTION}" = "fix" ]
then
	echo "Fix duplicate line for lang en_US"
	# shellcheck disable=2016
	for file in htdocs/langs/en_US/*.lang ; do
		awk -i inplace ' !x[$0]++' "$file"
	done
fi

exit $exit_code
