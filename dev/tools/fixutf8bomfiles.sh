#!/bin/sh
#
# Checks of fix files contains UTF-8 BOM in dolibarr source tree,
# excluding git repository, custom modules and included libraries.
#
# Raphaël Doursenaud - rdoursenaud@gpcsolutions.fr
# Laurent Destailleur  eldy@users.sourceforge.net
#------------------------------------------------------
# Usage: fixutf8bomfiles.sh [list|fix]
#------------------------------------------------------

# Syntax
if [ "x$1" != "xlist" -a "x$1" != "xfix" ]
then
    echo "Detect and fix bad UTF8 encoded files (UTF8 must not use BOM char)"
	echo "Usage: fixutf8bomfiles.sh (list|fix) [addincludes]"
fi

if [ "x$2" != "xaddincludes" ]
then
	export moreoptions="--exclude-dir='includes'"
fi

# To detec
if [ "x$1" = "xlist" ]
then
	#find . \( -iname '*.php' -print0 -o -iname '*.sh' -print0 -o -iname '*.pl' -print0 -o -iname '*.lang' -print0 -o -iname '*.txt' \) -print0 | xargs -0 awk '/^\xEF\xBB\xBF/ {print FILENAME} {nextfile}'
	echo "grep -rlIZ --include='*.php' --include='*.sh' --include='*.pl' --include='*.lang' --include='*.txt' --exclude-dir='.git' --exclude-dir='.tx' $moreoptions --exclude-dir='custom' . . | xargs -0 awk '/^\xEF\xBB\xBF/ {print FILENAME} {nextfile}'"
	grep -rlIZ --include='*.php' --include='*.sh' --include='*.pl' --include='*.lang' --include='*.txt' --exclude-dir='.git' --exclude-dir='.tx' $moreoptions --exclude-dir='custom' . . | xargs -0 awk '/^\xEF\xBB\xBF/ {print FILENAME} {nextfile}'
fi

# To convert
if [ "x$1" = "xfix" ]
then
	for fic in `grep -rlIZ --include='*.php' --include='*.sh' --include='*.pl' --include='*.lang' --include='*.txt' --exclude-dir='.git' --exclude-dir='.tx' $moreoptions --exclude-dir='custom' . . | xargs -0 awk '/^\xEF\xBB\xBF/ {print FILENAME} {nextfile}'`
	do
		echo "Fixing $fic"
		sed -i '1s/^\xEF\xBB\xBF//' $fic
	done;
fi
