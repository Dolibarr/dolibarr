#!/bin/sh
# Helps find duplicate translation keys in language files
#
# Copyright (C) 2014		Raphaël Doursenaud					<rdoursenaud@gpcsolutions.fr>
# Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>

exit_code=0
# Syntax
if [ "$1" != "list" ] && [ "$1" != "fix" ]
then
	echo "Detect duplicate translation keys inside a file (there is no cross file check)."
	echo "Usage: detectduplicatelangkey.sh (list|fix)"
	exit_code=1
fi


ACTION=$1

if [ "${ACTION}" = "list" ]
then
	echo "Search duplicate keys into en_US lang files (there is no cross file check)"
	for file in htdocs/langs/en_US/*.lang
	do
		dupes=$(
			sed "s/^\s*//" "$file" | # Remove any leading whitespace
			sed "s/\s*\=/=/" | # Remove any whitespace before =
			grep -Po "(^.*?)(?==)" | # Non greedy match everything before =
			sort | uniq -d | # Find duplicates
			while IFS= read -r key ; do
				grep -n "^$key" "$file" |
				# Format line to be recognised for code annotation by logToCs.py
				echo "$file:$(cut -d ':' -f 1 | tail -n 1):error:Duplicate '$key'"
			done
			# awk '$0="'"$file"':"$0' # Prefix with filename (for ci)
		)

		if [ -n "$dupes" ]
		then
			exit_code=1
			echo "$dupes"
		fi
	done
fi

# To convert
if [ "${ACTION}" = "fix" ]
then
	echo Feature not implemented. Please fix files manually.
	exit_code=1
fi

exit $exit_code
