#!/bin/bash
# Recursively deduplicate file lines on a per file basis
# Useful to deduplicate language files
#
# Needs awk 4.0 for the inplace fixing command
#
# Raphaël Doursenaud - rdoursenaud@gpcsolutions.fr

# Syntax
if [ "x$1" != "xlist" -a "x$1" != "xfix" ]
then
	echo "Usage: deduplicatefilelinesrecursively.sh [list|fix]"
fi

# To detect
if [ "x$1" = "xlist" ]
then
    for file in `find . -type f`
    do
        if [ `sort "$file" | uniq -d | wc -l` -gt 0 ]
        then
            echo "$file"
        fi
    done
fi

# To fix
if [ "x$1" = "xfix" ]
then
    for file in `find . -type f`
    do
    	awk -i inplace ' !x[$0]++' "$file"
    done;
fi
