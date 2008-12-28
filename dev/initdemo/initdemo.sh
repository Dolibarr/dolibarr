#!/bin/sh
#------------------------------------------------------
# Script to purge and init a database with demo values.
# Note: "dialog" tool need to be available.
#
# Régis Houssin - regis@dolibarr.fr
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# WARNING: This script erase all data of database
#------------------------------------------------------

export mydir=`echo "$_" | sed -e 's/initdemo.sh//'`;
export id=`id -u`;

# ----------------------------- check if root
if [ "x$id" != "x0" ]
then
	echo "Script must be ran as root"
	exit
fi

# ----------------------------- database name
DIALOG=${DIALOG=dialog}
DIALOG="$DIALOG --ascii-lines"
fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
trap "rm -f $fichtemp" 0 1 2 5 15
$DIALOG --title "Init Dolibarr with demo values" --clear \
        --inputbox "Mysql database name :" 16 51 dolibarrdemo 2> $fichtemp
valret=$?
case $valret in
  0)
base=`cat $fichtemp`;;
  1)
exit;;
  255)
exit;;
esac

# ---------------------------- database port
DIALOG=${DIALOG=dialog}
fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
trap "rm -f $fichtemp" 0 1 2 5 15
$DIALOG --title "Init Dolibarr with demo values" --clear \
        --inputbox "Mysql port (ex: 3306):" 16 51 3306 2> $fichtemp

valret=$?

case $valret in
  0)
port=`cat $fichtemp`;;
  1)
exit;;
  255)
exit;;
esac

# ---------------------------- compte admin mysql
DIALOG=${DIALOG=dialog}
fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
trap "rm -f $fichtemp" 0 1 2 5 15
$DIALOG --title "Init Dolibarr with demo values" --clear \
        --inputbox "Mysql root login (ex: root):" 16 51 root 2> $fichtemp

valret=$?

case $valret in
  0)
admin=`cat $fichtemp`;;
  1)
exit;;
  255)
exit;;
esac

# ---------------------------- mot de passe admin mysql
DIALOG=${DIALOG=dialog}
fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
trap "rm -f $fichtemp" 0 1 2 5 15
$DIALOG --title "Init Dolibarr with demo values" --clear \
        --inputbox "Password for Mysql root login :" 16 51 2> $fichtemp

valret=$?

case $valret in
  0)
passwd=`cat $fichtemp`;;
  1)
exit;;
  255)
exit;;
esac

# ---------------------------- chemin d'accès du répertoire documents
DIALOG=${DIALOG=dialog}
fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
trap "rm -f $fichtemp" 0 1 2 5 15
$DIALOG --title "Init Dolibarr with demo values" --clear \
        --inputbox "Full path to documents directory (ex: /var/www/dolibarr/documents)- no / at end :" 16 51 2> $fichtemp

valret=$?

case $valret in
  0)
docs=`cat $fichtemp`;;
  1)
exit;;
  255)
exit;;
esac

# ---------------------------- confirmation
DIALOG=${DIALOG=dialog}
$DIALOG --title "Init Dolibarr with demo values" --clear \
        --yesno "Do you confirm ? \n Mysql database : '$base' \n Mysql port : '$port' \n Mysql login: '$admin' \n Mysql password : '$passwd' \n Documents dir : '$docs'" 15 40

case $? in
        0)      echo "Ok, start process...";;
        1)      exit;;
        255)    exit;;
esac

# ---------------------------- run sql file
if [ "x$passwd" != "x" ]
then
	export passwd="-p$passwd"
fi
echo "mysql -u$admin $passwd $base < $mydir/initdemo.sql"
mysql -u$admin $passwd $base < $mydir/initdemo.sql

echo "Dolibarr data demo has been loaded."
echo
