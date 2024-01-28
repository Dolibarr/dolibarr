#!/bin/sh
#------------------------------------------------------
# Script to extract a database with demo values.
# Note: "dialog" tool need to be available if no parameter provided.
#
# Regis Houssin       - regis.houssin@inodbox.com
# Laurent Destailleur - eldy@users.sourceforge.net
#------------------------------------------------------
# Usage: savedemo.sh
# usage: savedemo.sh mysqldump_dolibarr_x.x.x.sql database port login pass
#------------------------------------------------------
# shellcheck disable=2012,2006,2034,2046,2064,2086,2155,2166,2186,2172,2268

export mydir=`echo "$0" | sed -e 's/savedemo.sh//'`;
if [ "x$mydir" = "x" ]
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
dumpfile=$1;
base=$2;
port=$3;
admin=$4;
passwd=$5;


# ----------------------------- if no params on command line
if [ "x$passwd" = "x" ]
then
	export dumpfile=`ls -v $mydir/mysqldump_dolibarr_*.sql | tail -n 1`
	export dumpfile=`basename $dumpfile`

	# ----------------------------- input file
	DIALOG=${DIALOG=dialog}
	DIALOG="$DIALOG --ascii-lines"
	fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
	trap "rm -f $fichtemp" 0 1 2 5 15
	$DIALOG --title "Save Dolibarr with demo values" --clear \
		--inputbox "Output dump file :" 16 55 $dumpfile 2> $fichtemp
	valret=$?
	case $valret in
		0)
			dumpfile=`cat $fichtemp` ;;
		1)
			exit ;;
		255)
			exit ;;
	esac

	# ----------------------------- database name
	DIALOG=${DIALOG=dialog}
	DIALOG="$DIALOG --ascii-lines"
	fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
	trap "rm -f $fichtemp" 0 1 2 5 15
	$DIALOG --title "Save Dolibarr with demo values" --clear \
		--inputbox "Mysql database name :" 16 55 dolibarrdemo 2> $fichtemp
	valret=$?
	case $valret in
		0)
			base=`cat $fichtemp` ;;
		1)
			exit ;;
		255)
			exit ;;
	esac

	# ---------------------------- database port
	DIALOG=${DIALOG=dialog}
	fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
	trap "rm -f $fichtemp" 0 1 2 5 15
	$DIALOG --title "Save Dolibarr with demo values" --clear \
		--inputbox "Mysql port (ex: 3306):" 16 55 3306 2> $fichtemp

	valret=$?

	case $valret in
		0)
			port=`cat $fichtemp` ;;
		1)
			exit ;;
		255)
			exit ;;
	esac

	# ---------------------------- compte admin mysql
	DIALOG=${DIALOG=dialog}
	fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
	trap "rm -f $fichtemp" 0 1 2 5 15
	$DIALOG --title "Save Dolibarr with demo values" --clear \
		--inputbox "Mysql root login (ex: root):" 16 55 root 2> $fichtemp

	valret=$?

	case $valret in
		0)
			admin=`cat $fichtemp` ;;
		1)
			exit ;;
		255)
			exit ;;
	esac

	# ---------------------------- Password for admin mysql (root)
	DIALOG=${DIALOG=dialog}
	fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
	trap "rm -f $fichtemp" 0 1 2 5 15
	$DIALOG --title "Save Dolibarr with demo values" --clear \
		--passwordbox "Password for Mysql root login :" 16 55 2> $fichtemp

	valret=$?

	case $valret in
		0)
			passwd=`cat $fichtemp` ;;
		1)
			exit ;;
		255)
			exit ;;
	esac

	# ---------------------------- chemin d'acces du repertoire documents
	#DIALOG=${DIALOG=dialog}
	#fichtemp=`tempfile 2>/dev/null` || fichtemp=/tmp/test$$
	#trap "rm -f $fichtemp" 0 1 2 5 15
	#$DIALOG --title "Save Dolibarr with demo values" --clear \
		#        --inputbox "Full path to documents directory (ex: /var/www/dolibarr/documents)- no / at end :" 16 55 2> $fichtemp

	#valret=$?

	#case $valret in
	#  0)
	#docs=`cat $fichtemp`;;
	#  1)
	#exit;;
	#  255)
	#exit;;
	#esac

	# ---------------------------- confirmation
	DIALOG=${DIALOG=dialog}
	$DIALOG --title "Save Dolibarr with demo values" --clear \
		--yesno "Do you confirm ? \n Dump file : '$dumpfile' \n Dump dir : '$mydir' \n Mysql database : '$base' \n Mysql port : '$port' \n Mysql login: '$admin' \n Mysql password : --hidden--" 15 55

	case $? in
		0)      echo "Ok, start process..." ;;
		1)      exit ;;
		255)    exit ;;
	esac

fi


# ---------------------------- run sql file
if [ "x$passwd" != "x" ]
then
	export passwd="-p$passwd"
fi
export list="
--ignore-table=$base.llx_abonne
--ignore-table=$base.llx_abonne_extrafields
--ignore-table=$base.llx_abonne_type
--ignore-table=$base.llx_abonnement
--ignore-table=$base.llx_accountingaccount
--ignore-table=$base.llx_accountingsystem
--ignore-table=$base.llx_advanced_extrafields
--ignore-table=$base.llx_advanced_extrafields_options
--ignore-table=$base.llx_advanced_extrafields_values
--ignore-table=$base.llx_agefodd_calendrier
--ignore-table=$base.llx_agefodd_certif_state
--ignore-table=$base.llx_agefodd_certificate_type
--ignore-table=$base.llx_agefodd_contact
--ignore-table=$base.llx_agefodd_convention
--ignore-table=$base.llx_agefodd_convention_stagiaire
--ignore-table=$base.llx_agefodd_cursus
--ignore-table=$base.llx_agefodd_cursus_extrafields
--ignore-table=$base.llx_agefodd_formateur
--ignore-table=$base.llx_agefodd_formateur_category
--ignore-table=$base.llx_agefodd_formateur_category_dict
--ignore-table=$base.llx_agefodd_formateur_training
--ignore-table=$base.llx_agefodd_formateur_type
--ignore-table=$base.llx_agefodd_formation_catalogue
--ignore-table=$base.llx_agefodd_formation_catalogue_extrafields
--ignore-table=$base.llx_agefodd_formation_catalogue_modules
--ignore-table=$base.llx_agefodd_formation_catalogue_type
--ignore-table=$base.llx_agefodd_formation_catalogue_type_bpf
--ignore-table=$base.llx_agefodd_formation_cursus
--ignore-table=$base.llx_agefodd_formation_objectifs_peda
--ignore-table=$base.llx_agefodd_opca
--ignore-table=$base.llx_agefodd_place
--ignore-table=$base.llx_agefodd_reg_interieur
--ignore-table=$base.llx_agefodd_session
--ignore-table=$base.llx_agefodd_session_adminsitu
--ignore-table=$base.llx_agefodd_session_admlevel
--ignore-table=$base.llx_agefodd_session_calendrier
--ignore-table=$base.llx_agefodd_session_commercial
--ignore-table=$base.llx_agefodd_session_contact
--ignore-table=$base.llx_agefodd_session_element
--ignore-table=$base.llx_agefodd_session_extrafields
--ignore-table=$base.llx_agefodd_session_formateur
--ignore-table=$base.llx_agefodd_session_formateur_calendrier
--ignore-table=$base.llx_agefodd_session_stagiaire
--ignore-table=$base.llx_agefodd_session_stagiaire_heures
--ignore-table=$base.llx_agefodd_session_status_type
--ignore-table=$base.llx_agefodd_stagiaire
--ignore-table=$base.llx_agefodd_stagiaire_certif
--ignore-table=$base.llx_agefodd_stagiaire_cursus
--ignore-table=$base.llx_agefodd_stagiaire_extrafields
--ignore-table=$base.llx_agefodd_stagiaire_type
--ignore-table=$base.llx_agefodd_training_admlevel
--ignore-table=$base.llx_askpricesupplier
--ignore-table=$base.llx_askpricesupplier_extrafields
--ignore-table=$base.llx_askpricesupplierdet
--ignore-table=$base.llx_askpricesupplierdet_extrafields
--ignore-table=$base.llx_assetOf
--ignore-table=$base.llx_assetOf_line
--ignore-table=$base.llx_asset_workstation_of
--ignore-table=$base.llx_asset_workstation_product
--ignore-table=$base.llx_asset_workstation_task
--ignore-table=$base.llx_assetof_amounts
--ignore-table=$base.llx_asset_workstation_of
--ignore-table=$base.llx_asset_workstation_of
--ignore-table=$base.llx_asset_workstation_of
--ignore-table=$base.llx_bookkeeping
--ignore-table=$base.llx_bootstrap
--ignore-table=$base.llx_bt_namemap
--ignore-table=$base.llx_bt_speedlimit
--ignore-table=$base.llx_bt_summary
--ignore-table=$base.llx_bt_timestamps
--ignore-table=$base.llx_bt_webseedfiles
--ignore-table=$base.llx_c_civilite
--ignore-table=$base.llx_c_dolicloud_plans
--ignore-table=$base.llx_c_pays
--ignore-table=$base.llx_c_source
--ignore-table=$base.llx_c_ticketsup_category
--ignore-table=$base.llx_c_ticketsup_severity
--ignore-table=$base.llx_c_ticketsup_type
--ignore-table=$base.llx_cabinetmed_c_banques
--ignore-table=$base.llx_cabinetmed_c_examconclusion
--ignore-table=$base.llx_cabinetmed_cons_extrafields
--ignore-table=$base.llx_cabinetmed_diaglec
--ignore-table=$base.llx_cabinetmed_examaut
--ignore-table=$base.llx_cabinetmed_exambio
--ignore-table=$base.llx_cabinetmed_examenprescrit
--ignore-table=$base.llx_cabinetmed_motifcons
--ignore-table=$base.llx_cabinetmed_patient
--ignore-table=$base.llx_cabinetmed_societe
--ignore-table=$base.llx_congespayes
--ignore-table=$base.llx_congespayes_config
--ignore-table=$base.llx_congespayes_events
--ignore-table=$base.llx_congespayes_logs
--ignore-table=$base.llx_congespayes_users
--ignore-table=$base.llx_dolicloud_customers
--ignore-table=$base.llx_dolicloud_stats
--ignore-table=$base.llx_dolicloud_emailstemplates
--ignore-table=$base.llx_dolireport_column
--ignore-table=$base.llx_dolireport_criteria
--ignore-table=$base.llx_dolireport_graph
--ignore-table=$base.llx_dolireport_plot
--ignore-table=$base.llx_dolireport_report
--ignore-table=$base.llx_domain
--ignore-table=$base.llx_ecommerce_category
--ignore-table=$base.llx_ecommerce_commande
--ignore-table=$base.llx_ecommerce_facture
--ignore-table=$base.llx_ecommerce_product
--ignore-table=$base.llx_ecommerce_site
--ignore-table=$base.llx_ecommerce_societe
--ignore-table=$base.llx_ecommerce_socpeople
--ignore-table=$base.llx_element_rang
--ignore-table=$base.llx_element_tag
--ignore-table=$base.llx_eleves
--ignore-table=$base.llx_eleves_extrafields
--ignore-table=$base.llx_entity
--ignore-table=$base.llx_entity_extrafields
--ignore-table=$base.llx_entity_thirdparty
--ignore-table=$base.llx_equipement_factory
--ignore-table=$base.llx_factory
--ignore-table=$base.llx_factory_extrafields
--ignore-table=$base.llx_factorydet
--ignore-table=$base.llx_filemanager_roots
--ignore-table=$base.llx_fournisseur_ca
--ignore-table=$base.llx_google_maps
--ignore-table=$base.llx_lead
--ignore-table=$base.llx_lead_extrafields
--ignore-table=$base.llx_milestone
--ignore-table=$base.llx_milestone
--ignore-table=$base.llx_monitoring_probes
--ignore-table=$base.llx_m
--ignore-table=$base.llx_m_extrafields
--ignore-table=$base.llx_monmodule_abcdef
--ignore-table=$base.llx_notes
--ignore-table=$base.llx_packages
--ignore-table=$base.llx_packages_extrafields
--ignore-table=$base.llx_pos_cash
--ignore-table=$base.llx_pos_control_cash
--ignore-table=$base.llx_pos_facture
--ignore-table=$base.llx_pos_moviments
--ignore-table=$base.llx_pos_ticketdet
--ignore-table=$base.llx_pos_paiement_ticket
--ignore-table=$base.llx_pos_places
--ignore-table=$base.llx_pos_ticket
--ignore-table=$base.llx_printer_ipp
--ignore-table=$base.llx_publi_c_contact_list
--ignore-table=$base.llx_publi_c_dnd_list
--ignore-table=$base.llx_publi_c_method_list
--ignore-table=$base.llx_residence
--ignore-table=$base.llx_residence_building
--ignore-table=$base.llx_residence_building_links
--ignore-table=$base.llx_scaninvoices_filestoimport
--ignore-table=$base.llx_scaninvoices_filestoimport_extrafields
--ignore-table=$base.llx_scaninvoices_settings
--ignore-table=$base.llx_scaninvoices_settings_extrafields
--ignore-table=$base.llx_sellyoursaas_blacklistcontent
--ignore-table=$base.llx_sellyoursaas_blacklistdir
--ignore-table=$base.llx_sellyoursaas_blacklistfrom
--ignore-table=$base.llx_sellyoursaas_blacklistip
--ignore-table=$base.llx_sellyoursaas_blacklistmail
--ignore-table=$base.llx_sellyoursaas_blacklistto
--ignore-table=$base.llx_sellyoursaas_deploymentserver
--ignore-table=$base.llx_sellyoursaas_stats
--ignore-table=$base.llx_sellyoursaas_whitelistip
--ignore-table=$base.llx_societe_rib2
--ignore-table=$base.llx_sellyoursaas_cancellation
--ignore-table=$base.llx_ticketsup
--ignore-table=$base.llx_ultimatepdf
--ignore-table=$base.llx_update_modules
--ignore-table=$base.llx_ventilation_achat
--ignore-table=$base.tmp_llx_accouting_account
--ignore-table=$base.tmp_llx_product_batch
--ignore-table=$base.tmp_llx_product_batch2
--ignore-table=$base.tmp_tmp
--ignore-table=$base.tmp_user
"
echo "mysqldump -P$port -u$admin -p***** $list $base > $mydir/$dumpfile"
mysqldump -P$port -u$admin $passwd $list $base > $mydir/$dumpfile
export res=$?

if [ "x$res" = "x0" ]
then
	echo "Success, file successfully loaded."
else
	echo "Error, load failed."
fi
echo
