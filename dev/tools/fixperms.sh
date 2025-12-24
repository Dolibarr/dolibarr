#!/bin/sh
#------------------------------------------------------
# Script to set/fix permissions on files
#
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# Usage: fixperms.sh (list|fix)
#------------------------------------------------------

# shellcheck disable=2166,2268

# Syntax
if [ "x$1" != "xlist" -a "x$1" != "xfix" ]
then
	echo "Fix permissions of files"
	echo "Usage: fixperms.sh (list|fix)"
fi

# To detect
if [ "x$1" = "xlist" ]
then
	echo Feature not yet available
fi

# To convert
if [ "x$1" = "xfix" ]
then
	find ./htdocs -type f -iname "*.php" -exec chmod a-x {} \;
	find ./htdocs/install/ -type d -exec chmod ug+rw {} \;
	chmod a+x ./scripts/*/*.php
	chmod a+x ./scripts/*/*.sh
	chmod g-w ./scripts/*/*.php
	chmod g-w ./scripts/*/*.sh
fi
