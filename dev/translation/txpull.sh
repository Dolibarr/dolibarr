#!/bin/bash
#------------------------------------------------------
# Script to pull language files to Transifex
#
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# Usage: txpull.sh (all|xx_XX) [-r dolibarr.file] [-f]
#------------------------------------------------------

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
cd $DIR/../..

# Syntax
if [ "x$1" = "x" ]
then
	echo "This pull remote transifex files to local dir."
	echo "Note:  If you pull a language file (not source), file will be skipped if local file is newer."
	echo "       Using -f will overwrite local file (does not work with 'all')."
	echo "       Using -s will force fetching of source file (avoid it, use en_US as language instead)."
	echo "       Using en_US as language parameter will update source language from transifex (en_US is excluded from 'all')."
	echo "Usage: ./dev/translation/txpull.sh (all|en_US|xx_XX) [-r dolibarr.file] [-f] [-s]"
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
	    echo "tx pull -a"
	    tx pull -a
	    
	    echo "Remove some language directories (not enough translated)"
	    rm -fr htdocs/langs/ach
	    rm -fr htdocs/langs/br_FR
	    rm -fr htdocs/langs/en
	    rm -fr htdocs/langs/frp
	    rm -fr htdocs/langs/fy_NL
	    
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
echo "For v11: Replace also regex \(.*(sponge|cornas|eratosthene|cyan).*\) with '' on *.lang files"

