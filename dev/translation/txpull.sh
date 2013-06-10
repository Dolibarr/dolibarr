#!/bin/sh
#------------------------------------------------------
# Script to pull language files to Transifex
#
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# Usage: txpull.sh [all|xx_XX]
#------------------------------------------------------

# Syntax
if [ "x$1" = "x" ]
then
	echo "Usage: txpull.sh (all|xx_XX) [-r dolibarr.file]"
	exit
fi


if [ "x$1" = "xall" ]
then
	for fic in ar_SA bg_BG ca_ES da_DK de_DE el_GR es_ES et_EE fa_IR fi_FI fr_FR hu_HU is_IS it_IT ja_JP nb_NO nl_NL pl_PL pt_PT ro_RO ru_RU ru_UA sl_SI sv_SE tr_TR zh_CN zh_TW
	do
		echo "tx pull -l $fic $2 $3"
		tx pull -l $fic $2 $3
	done
else
	echo "tx pull -l $1 $2 $3"
	tx pull -l $1 $2 $3
fi

