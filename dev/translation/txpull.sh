#!/bin/sh
#------------------------------------------------------
# Script to pull language files to Transifex
#
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# Usage: txpull.sh (all|xx_XX) [-r dolibarr.file] [-f]
#------------------------------------------------------

# Syntax
if [ "x$1" = "x" ]
then
	echo "This pull remote transifex files to local dir."
	echo "Note:  If you pull a language file (not source), file will be skipped if local file is newer."
	echo "       Using -f will overwrite local file (does not work with 'all')."
	echo "Usage: ./dev/translation/txpull.sh (all|xx_XX) [-r dolibarr.file] [-f] [-s]"
	exit
fi

if [ ! -d ".tx" ]
then
	echo "Script must be ran from root directory of project with command ./dev/translation/txpull.sh"
	exit
fi


if [ "x$1" = "xall" ]
then
	if [ "x$2" = "x" ]
	then
	    echo "tx pull"
	    tx pull
	else
		for dir in `find htdocs/langs/* -type d`
		do
		    fic=`basename $dir`
		    if [ $fic != "en_US" ]
		    then
			    echo "tx pull -l $fic $2 $3"
			    tx pull -l $fic $2 $3
			fi
		done
	fi
	cd -
else
	echo "tx pull -l $1 $2 $3 $4 $5"
	tx pull -l $1 $2 $3 $4 $5
fi

echo Think to launch also: 
echo "> dev/tools/fixaltlanguages.sh fix all"
