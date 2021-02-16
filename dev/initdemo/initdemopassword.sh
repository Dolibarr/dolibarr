#!/bin/sh
#------------------------------------------------------
# Script to reinit admin password.
# Note: "dialog" tool need to be available if no parameter provided.
#
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# Usage: initdemopassword.sh confirm 
# usage: initdemopassword.sh confirm base port login pass
#------------------------------------------------------


export mydir=`echo "$0" | sed -e 's/initdemopassword.sh//'`;
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
base=$2;
port=$3;
demologin=$4;
demopass=$5;

# ----------------------------- check params
if [ "x$confirm" != "xconfirm" ]
then
	echo "----- $0 -----"
	echo "Usage: initdemopassword.sh confirm [base port login pass]"
	exit
fi


# ----------------------------- if no params on command line
if [ "x$demopass" = "x" ]
then
	export dumpfile=`ls -v $mydir/mysqldump_dolibarr_*.sql | tail -n 1`
	export dumpfile=`basename $dumpfile`

	# ----------------------------- database name
	DIALOG=${DIALOG=dialog}
	DIALOG="$DIALOG --ascii-lines"
	fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
	trap "rm -f $fichtemp" 0 1 2 5 15
	$DIALOG --title "Reset login password" --clear \
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
	rm $fichtemp
	
	# ---------------------------- database port
	DIALOG=${DIALOG=dialog}
	fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
	trap "rm -f $fichtemp" 0 1 2 5 15
	$DIALOG --title "Reset login password" --clear \
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
	rm $fichtemp
	
	
	# ----------------------------- demo login
	DIALOG=${DIALOG=dialog}
	DIALOG="$DIALOG --ascii-lines"
	fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
	trap "rm -f $fichtemp" 0 1 2 5 15
	$DIALOG --title "Reset login password" --clear \
	        --inputbox "Login to reset :" 16 55 dolibarrdemologin 2> $fichtemp
	valret=$?
	case $valret in
	  0)
	demologin=`cat $fichtemp`;;
	  1)
	exit;;
	  255)
	exit;;
	esac
	rm fichtemp
	
	# ----------------------------- demo pass
	DIALOG=${DIALOG=dialog}
	DIALOG="$DIALOG --ascii-lines"
	fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
	trap "rm -f $fichtemp" 0 1 2 5 15
	$DIALOG --title "Reset login password" --clear \
	        --inputbox "Pass to set :" 16 55 dolibarrdemopass 2> $fichtemp
	valret=$?
	case $valret in
	  0)
	demopass=`cat $fichtemp`;;
	  1)
	exit;;
	  255)
	exit;;
	esac
	rm fichtemp
	
	
	export documentdir=`cat $mydir/../../htdocs/conf/conf.php | grep '^\$dolibarr_main_data_root' | sed -e 's/$dolibarr_main_data_root=//' | sed -e 's/;//' | sed -e "s/'//g" | sed -e 's/"//g' `


	# ---------------------------- confirmation
	DIALOG=${DIALOG=dialog}
	$DIALOG --title "Reset login password" --clear \
	        --yesno "Do you confirm ? \n Mysql database : '$base' \n Mysql port : '$port' \n Demo login: '$demologin' \n Demo password : '$demopass'" 15 55
	
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
echo "echo \"UPDATE llx_user SET pass_crypted = MD5('$demopass') WHERE login = '$demologin';\" | mysql -P$port $base"
echo "UPDATE llx_user SET pass_crypted = MD5('$demopass') WHERE login = '$demologin';" | mysql -P$port $base
export res=$?

if [ $res -ne 0 ]; then
	echo "Error to execute sql with mysql -P$port -u$admin -p***** $base"
	exit
fi 


if [ "x$res" = "x0" ]
then
	echo "Success, file successfully loaded."
else
	echo "Error, load failed."
fi
echo
