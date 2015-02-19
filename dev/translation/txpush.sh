#!/bin/sh
#------------------------------------------------------
# Script to push language files to Transifex
#
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# Usage: txpush.sh (source|all|xx_XX) [-r dolibarr.file] [-f]
#------------------------------------------------------

# Syntax
if [ "x$1" = "x" ]
then
	echo "This push local files to transifex."
	echo "Note:  If you push a language file (not source), file will be skipped if transifex file is newer."
	echo "       Using -f will overwrite translation but not memory."
	echo "Usage: ./dev/translation/txpush.sh (source|all|xx_XX) [-r dolibarr.file] [-f] [--no-interactive]"
	exit
fi

if [ ! -d ".tx" ]
then
	echo "Script must be ran from root directory of project with command ./dev/translation/txpush.sh"
	exit
fi

if [ "x$1" = "xall" ]
then
	for fic in ar_SA bg_BG bs_BA ca_ES cs_CZ da_DK de_DE el_GR es_ES et_EE eu_ES fa_IR fi_FI fr_FR he_IL hr_HR hu_HU id_ID is_IS it_IT ja_JP ka_GE ko_KR lt_LT lv_LV mk_MK nb_NO nl_NL pl_PL pt_PT ro_RO ru_RU ru_UA sk_SK sl_SI sq_AL sv_SE th_TH tr_TR uk_UA uz_UZ vi_VN zh_CN zh_TW
	do
		echo "tx push --skip -t -l $fic $2 $3"
		tx push --skip -t -l $fic $2 $3
	done
else
if [ "x$1" = "xsource" ]
then
	echo "tx push -s $2 $3"
	tx push -s $2 $3 
else
	echo "tx push --skip -t -l $1 $2 $3 $4"
	tx push --skip -t -l $1 $2 $3 $4
fi
fi
