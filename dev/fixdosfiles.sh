#!/bin/sh
#------------------------------------------------------
# Script to find files that are not Unix encoded
#
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# Usage: fixdosfiles.sh [list|fix]
#------------------------------------------------------

# Syntax
if [ "x$1" != "xlist" -a "x$1" != "xfix" ]
then
	echo "Usage: fixdosfiles.sh [list|fix]"
fi

# To detec
if [ "x$1" = "xlist" ]
then
	find . -type f -iname "*.php" -exec file "{}" + | grep CRLF
fi

# To convert
if [ "x$1" = "xfix" ]
then
	for fic in `find . -type f -iname "*.php" -exec file "{}" + | grep CRLF | awk -F':' '{ print $1 }' `
	do
		echo "Fix file $fic"
		dos2unix $fic
	done;
fi
