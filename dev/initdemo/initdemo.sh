#!/bin/sh
#------------------------------------------------------
# Script to purge and init a database with demo values.
# Note: "dialog" tool need to be available if no parameter provided.
#
# WARNING: This script erase all data of database
# with data into dump file
#
# Regis Houssin       - regis.houssin@inodbox.com
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# Usage: initdemo.sh confirm 
# usage: initdemo.sh confirm mysqldump_dolibarr_x.x.x.sql database port login pass
#------------------------------------------------------


export mydir=`echo "$0" | sed -e 's/initdemo.sh//'`;
if [ "x$mydir" = 'x' -o "x$mydir" = 'x./' ]
then
    export mydir="."
fi
export id=`id -u`;


# ----------------------------- check if root
if [ "x$id" != "x0" -a "x$id" != "x1001" ]
then
	echo "Script must be ran as root"
	exit
fi


# ----------------------------- command line params
confirm=$1;
dumpfile=$2;
base=$3;
port=$4;
admin=$5;
passwd=$6;

# ----------------------------- check params
if [ "x$confirm" != "xconfirm" ]
then
	echo "----- $0 -----"
	echo "Usage: initdemo.sh confirm [mysqldump_dolibarr_x.x.x.sql database port login pass]"
	exit
fi


# ----------------------------- if no params on command line
if [ "x$passwd" = "x" ]
then
	export dumpfile=`ls $mydir/mysqldump_dolibarr_*.sql | sort | tail -n 1`
	export dumpfile=`basename $dumpfile`

	# ----------------------------- input file
	DIALOG=${DIALOG=dialog}
	DIALOG="$DIALOG --ascii-lines"
	fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
	trap "rm -f $fichtemp" 0 1 2 5 15
	$DIALOG --title "Init Dolibarr with demo values" --clear \
	        --inputbox "Input dump file :" 16 55 $dumpfile 2> $fichtemp
	valret=$?
	case $valret in
	  0)
	dumpfile=`cat $fichtemp`;;
	  1)
	exit;;
	  255)
	exit;;
	esac
	
	# ----------------------------- database name
	DIALOG=${DIALOG=dialog}
	DIALOG="$DIALOG --ascii-lines"
	fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
	trap "rm -f $fichtemp" 0 1 2 5 15
	$DIALOG --title "Init Dolibarr with demo values" --clear \
	        --inputbox "Mysql database name :" 16 55 dolibarrdemo 2> $fichtemp
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
	        --inputbox "Mysql port (ex: 3306):" 16 55 3306 2> $fichtemp
	
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
	        --inputbox "Mysql root login (ex: root):" 16 55 root 2> $fichtemp
	
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
	        --inputbox "Password for Mysql root login :" 16 55 2> $fichtemp
	
	valret=$?
	
	case $valret in
	  0)
	passwd=`cat $fichtemp`;;
	  1)
	exit;;
	  255)
	exit;;
	esac
	
	
	export documentdir=`cat $mydir/../../htdocs/conf/conf.php | grep '^\$dolibarr_main_data_root' | sed -e 's/$dolibarr_main_data_root=//' | sed -e 's/;//' | sed -e "s/'//g" | sed -e 's/"//g' `


	# ---------------------------- confirmation
	DIALOG=${DIALOG=dialog}
	$DIALOG --title "Init Dolibarr with demo values" --clear \
	        --yesno "Do you confirm ? \n Dump file : '$dumpfile' \n Dump dir : '$mydir' \n Document dir : '$documentdir' \n Mysql database : '$base' \n Mysql port : '$port' \n Mysql login: '$admin' \n Mysql password : '$passwd'" 15 55
	
	case $? in
	        0)      echo "Ok, start process...";;
	        1)      exit;;
	        255)    exit;;
	esac

fi


# ---------------------------- run sql file
if [ "x$passwd" != "x" ]
then
	export passwd="-p$passwd"
fi
#echo "mysql -P$port -u$admin $passwd $base < $mydir/$dumpfile"
#mysql -P$port -u$admin $passwd $base < $mydir/$dumpfile
#echo "drop old table"
echo "drop table if exists llx_accounting_account;" | mysql -P$port -u$admin $passwd $base
echo "mysql -P$port -u$admin -p***** $base < $mydir/$dumpfile"
mysql -P$port -u$admin $passwd $base < $mydir/$dumpfile
export res=$?


# ---------------------------- copy demo files
export documentdir=`cat $mydir/../../htdocs/conf/conf.php | grep '^\$dolibarr_main_data_root' | sed -e 's/$dolibarr_main_data_root=//' | sed -e 's/;//' | sed -e "s/'//g" | sed -e 's/"//g' `
if [ "x$documentdir" != "x" ]
then
	$DIALOG --title "Reset document directory tpp" --clear \
	        --inputbox "Delete and recreate document directory $documentdir/:" 16 55 n 2> $fichtemp
	
	valret=$?
	
	case $valret in
	  0)
	rep=`cat $fichtemp`;;
	  1)
	exit;;
	  255)
	exit;;
	esac
	
	echo "rep=$rep"
	if [ "x$rep" = "xy" ]; then
		echo rm -fr "$documentdir/*"
		rm -fr $documentdir/*
	fi
		
	echo cp -pr $mydir/documents_demo/* "$documentdir/"
	cp -pr $mydir/documents_demo/* "$documentdir/"
	
	mkdir "$documentdir/doctemplates/" 2>/dev/null
	echo cp -pr $mydir/../../htdocs/install/doctemplates/* "$documentdir/doctemplates/"
	cp -pr $mydir/../../htdocs/install/doctemplates/* "$documentdir/doctemplates/"
	
	echo cp -pr $mydir/../../htdocs/install/medias/* "$documentdir/medias/image/"
	cp -pr $mydir/../../htdocs/install/medias/* "$documentdir/medias/image/"

	mkdir -p "$documentdir/ecm/Administrative documents" 2>/dev/null
	mkdir -p "$documentdir/ecm/Images" 2>/dev/null
	rm -f "$documentdir/doctemplates/"*/index.html
	echo cp -pr $mydir/../../doc/images/* "$documentdir/ecm/Images"
	cp -pr $mydir/../../doc/images/* "$documentdir/ecm/Images"
	
	chmod -R u+w "$documentdir/"
	chown -R www-data "$documentdir/"
else
	echo Detection of documents directory from $mydir failed so demo files were not copied. 
fi



if [ "x$res" = "x0" ]
then
	echo "Success, file successfully loaded."
else
	echo "Error, load failed."
fi
echo
