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
rm ../../htdocs/conf/conf.php
