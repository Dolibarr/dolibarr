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
	echo "Usage: txpull.sh (all|xx_XX) [-r dolibarr.file] [-f]"
	exit
fi


if [ "x$1" = "xall" ]
then
	for fic in ar_SA bg_BG ca_ES da_DK de_DE el_GR es_ES et_EE fa_IR fi_FI fr_FR he_IL hu_HU is_IS it_IT ja_JP ko_KR nb_NO nl_NL pl_PL pt_PT ro_RO ru_RU ru_UA sl_SI sv_SE tr_TR vi_VN zh_CN zh_TW
	do
		echo "tx pull -l $fic $2 $3"
		tx pull -l $fic $2 $3
	done
else
	echo "tx pull -l $1 $2 $3 $4"
	tx pull -l $1 $2 $3 $4
fi

