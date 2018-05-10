#!/bin/sh
# Recursively deduplicate file lines on a per file basis
# Useful to deduplicate language files
#
# Needs awk 4.0 for the inplace fixing command
#
# Raphaël Doursenaud - rdoursenaud@gpcsolutions.fr

# Syntax
if [ "x$1" != "xlist" -a "x$1" != "xfix" ]
then
	echo "Find exact duplicated lines into file (not cross file checking)"
	echo "Usage: deduplicatefilelinesrecursively.sh [list|fix]"
fi

# To detect
if [ "x$1" = "xlist" ]
then
	echo "Search duplicate line for lang en_US"
    for file in `find htdocs/langs/en_US -type f -name *.lang`
    do
        if [ `sort "$file" | grep -v '^$' | uniq -d | wc -l` -gt 0 ]
        then
            echo "***** $file"
            sort "$file" | grep -v '^$' | uniq -d
        fi
    done
fi

# To fix
if [ "x$1" = "xfix" ]
then
	echo "Fix duplicate line for lang en_US"
    for file in `find htdocs/langs/en_US -type f -name *.lang`
    do
    	awk -i inplace ' !x[$0]++' "$file"
    done;
fi
