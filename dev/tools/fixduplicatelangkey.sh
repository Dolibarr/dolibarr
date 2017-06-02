#!/bin/sh
# Helps find duplicate translation keys in language files
#
# Copyright (C) 2014 Raphaël Doursenaud - rdoursenaud@gpcsolutions.fr


# Syntax
if [ "x$1" != "xlist" -a "x$1" != "xfix" ]
then
    echo "Detect duplicate translation keys inside a file (there is no cross file check)."
	echo "Usage: detectduplicatelangkey.sh (list|fix)"
fi


if [ "x$1" = "xlist" ]
then
	echo "Search duplicate keys into en_US lang files (there is no cross file check)"
	for file in `find htdocs/langs/en_US -name *.lang -type f`
	do
	    dupes=$(
	    sed "s/^\s*//" "$file" | # Remove any leading whitespace
	    sed "s/\s*\=/=/" | # Remove any whitespace before =
	    grep -Po "(^.*?)=" | # Non greedeely match everything before =
	    sed "s/\=//" | # Remove trailing = so we get the key
	    sort | uniq -d # Find duplicates
	    )
	
	    if [ -n "$dupes" ]
	    then
	        echo "Duplicates found in $file"
	        echo "$dupes"
	    fi
	done
fi

# To convert
if [ "x$1" = "xfix" ]
then
	echo Feature not implemented. Please fix files manually.
fi
