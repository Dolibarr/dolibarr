#!/bin/sh
#------------------------------------------------------
# Detect files that does not contains any tab inside
#
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# Usage: fixnotabfiles.sh [list|fix]
#------------------------------------------------------

# Syntax
if [ "$1" != "list" ] && [ "$1" != "fix" ]
then
	echo "Detect .sh and .spec files that does not contain any tab"
	echo "Usage: fixnotabfiles.sh [list|fix]"
fi

# List/Detect files
if [ "$1" = "list" ]
then
	find build \( -iname "*.sh" -o -iname "*.spec" \) -exec grep -L -P '\t' {} \;
fi

# Fix/convert files
if [ "$1" = "fix" ]
then
	echo Feature not implemented. Please fix files manually.
fi
