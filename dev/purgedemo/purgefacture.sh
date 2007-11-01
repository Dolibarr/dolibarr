#!/bin/sh
#
# Régis Houssin - regis@dolibarr.fr
# Purge des factures
#
#
# si pas d'arguments passés on les demandes
if [ ! -n "$1" ]; then
		DIALOG=${DIALOG=dialog}
		fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
		trap "rm -f $fichtemp" 0 1 2 5 15
		$DIALOG --title "Suppression des factures" --clear \
				--inputbox "Nom de la base Mysql :" 16 51 2> $fichtemp
		valret=$?
		case $valret in
		  0)
		base=`cat $fichtemp`;;
		  1)
		exit;;
		  255)
		exit;;
		esac
		DIALOG=${DIALOG=dialog}
		fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
		trap "rm -f $fichtemp" 0 1 2 5 15
		$DIALOG --title "Suppression des factures" --clear \
				--inputbox "Compte Admin Mysql (ex: root):" 16 51 2> $fichtemp
		valret=$?
		case $valret in
		  0)
		admin=`cat $fichtemp`;;
		  1)
		exit;;
		  255)
		exit;;
		esac
		DIALOG=${DIALOG=dialog}
		fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
		trap "rm -f $fichtemp" 0 1 2 5 15
		$DIALOG --title "Suppression des factures" --clear \
				--inputbox "Mot de passe du compte Admin Mysql :" 16 51 2> $fichtemp
		valret=$?
		case $valret in
		  0)
		passwd=`cat $fichtemp`;;
		  1)
		exit;;
		  255)
		exit;;
		esac
		DIALOG=${DIALOG=dialog}
		fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
		trap "rm -f $fichtemp" 0 1 2 5 15
		$DIALOG --title "Suppression des factures" --clear \
				--inputbox "Chemin complet du répertoire documents (ex: /var/www/dolibarr/documents)- pas de / à la fin :" 16 51 2> $fichtemp
		valret=$?
		case $valret in
		  0)
		docs=`cat $fichtemp`;;
		  1)
		exit;;
		  255)
		exit;;
		esac
else
	base=$1;
	admin=$2;
	passwd=$3;
	docs=$4;
fi
echo "####### Suppression des factures #######"
mysql -u$admin -p$passwd $base < purge-facture.sql
rm -rf $docs/facture/*
mkdir $docs/facture/images
chmod 2755 $docs/facture/images
chown www-data $docs/facture/images
