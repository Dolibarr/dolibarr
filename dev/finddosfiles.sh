#!/bin/sh
#------------------------------------------------------
# Script to find files that are not Unix encoded
#
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# Usage: finddosfiles.sh
#------------------------------------------------------

# To detec
find . -type f -iname "*.php"  -exec file "{}" + | grep CRLF

# To convert
#find . -type f -iname "*.php"  -exec dos2unix "{}" +;

