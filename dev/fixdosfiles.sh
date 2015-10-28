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
	echo "This script detect or clean files with CR+LF into files with LF only. All source files are included, also files into includes."
	echo "Usage: fixdosfiles.sh [list|fix]"
fi

# To detec
if [ "x$1" = "xlist" ]
then
	find . \( -iname "*.md" -o -iname "*.html" -o -iname "*.htm" -o -iname "*.php" -o -iname "*.sh" -o -iname "*.cml" -o -iname "*.css" -o -iname "*.js" -o -iname "*.lang" -o -iname "*.pl" -o -iname "*.txt" -o -iname "*.xml" \) -exec file "{}" + | grep CRLF
#	find . \( -iname "*.md" -o -iname "*.html" -o -iname "*.htm" -o -iname "*.php" -o -iname "*.sh" -o -iname "*.cml" -o -iname "*.css" -o -iname "*.js" -o -iname "*.lang" -o -iname "*.pl" -o -iname "*.txt" -o -iname "*.xml" \) -exec file "{}" + | grep -v 'htdocs\/includes' | grep CRLF
fi

# To convert
if [ "x$1" = "xfix" ]
then
	for fic in `find . \( -iname "*.md" -o -iname "*.html" -o -iname "*.htm" -o -iname "*.php" -o -iname "*.sh" -o -iname "*.cml" -o -iname "*.css" -o -iname "*.js" -o -iname "*.lang" -o -iname "*.pl" -o -iname "*.txt" -o -iname "*.xml" \) -exec file "{}" + | grep CRLF | awk -F':' '{ print $1 }' `
	do
		echo "Fix file $fic"
		dos2unix $fic
	done;
fi
