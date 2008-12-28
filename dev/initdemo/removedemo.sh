#!/bin/sh
#------------------------------------------------------
# Script to remove setup of a Dolibarr installation.
# Note: "dialog" tool need to be available.
#
# Régis Houssin - regis@dolibarr.fr
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# WARNING: This script erase all data of database
#------------------------------------------------------

export mydir=`echo "$_" | sed -e 's/removedemo.sh//'`;

DIALOG=${DIALOG=dialog}
DIALOG="$DIALOG --ascii-lines"
fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
trap "rm -f $fichtemp" 0 1 2 5 15
$DIALOG --title "Remove Dolibarr install" --clear \
		--yesno "Do you confirm ?" 15 40
valret=$?
case $valret in
  0)
base=`cat $fichtemp`;;
  1)
exit;;
  255)
exit;;
esac

# ---------------------------- remove conf file
echo "Remove file $mydir../../htdocs/conf/conf.php"
cp -pf $mydir../../htdocs/conf/conf.php $mydir../../htdocs/conf/conf.sav.php 2>/dev/null
rm $mydir../../htdocs/conf/conf.php 2>/dev/null
echo "Remove file $mydir../../install.lock"
rm $mydir../../install.lock 2>/dev/null

echo "Dolibarr demo has been removed (need to be installed again)."
echo
