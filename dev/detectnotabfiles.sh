#!/bin/sh
#------------------------------------------------------
# Detect files that does not contains any tab inside
#
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# Usage: fixnotabfiles.sh [list|fix]
#------------------------------------------------------

# Syntax
if [ "x$1" != "xlist" -a "x$1" != "xfix" ]
then
	echo "Usage: fixnotabfiles.sh [list|fix]"
fi

# To detec
if [ "x$1" = "xlist" ]
then
	find build \( -iname "*.sh" -o -iname "*.spec" \) -exec grep -l -P '\t' {} \;
fi

# To convert
if [ "x$1" = "xfix" ]
then
	echo Feature not implemented. Please fix files manually.
fi
