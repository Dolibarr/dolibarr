#!/usr/bin/env php
<?php
/* Copyright (C) 2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016 Juanjo Menent        <jmenent@2byte.es>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * THIS SCRIPT DELETE ALL MAIN TABLE CONTENT
 * WARNING, DO NOT USE ON A PRODUCTION INSTANCE
 */

/**
 *      \file       dev/initdata/purge-data.php
 *      \brief      Script to delete all main tables
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=__DIR__.'/';

// Test si mode batch
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

// Recupere root dolibarr
$path=preg_replace('/purge-data.php/i', '', $_SERVER["PHP_SELF"]);
require $path."../../htdocs/master.inc.php";
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

$langs->loadLangs(array("main", "errors"));

// Global variables
$version=DOL_VERSION;
$error=0;

// List of sql to execute
$sqls=array(
	'user'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user IN (SELECT rowid FROM ".MAIN_DB_PREFIX."user WHERE admin = 0 and login != 'admin') AND fk_user IN (select rowid FROM ".MAIN_DB_PREFIX."user where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."user WHERE admin = 0 and login != 'admin' AND datec < '__DATE__'",
	),
	'event'=>array(
		//"DELETE FROM ".MAIN_DB_PREFIX."actioncomm WHERE lineid IN (SELECT rowid FROM ".MAIN_DB_PREFIX."bank WHERE datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."actioncomm WHERE datec < '__DATE__'",
	),
	'payment'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."paiement_facture where fk_facture IN (select rowid FROM ".MAIN_DB_PREFIX."facture where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."paiement where rowid NOT IN (SELECT fk_paiement FROM ".MAIN_DB_PREFIX."paiement_facture)",
	),
	'supplier_payment'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."paiementfourn_facturefourn where fk_facturefourn IN (select rowid FROM ".MAIN_DB_PREFIX."facture_fourn where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."paiementfourn where rowid NOT IN (SELECT fk_paiementfourn FROM ".MAIN_DB_PREFIX."paiementfourn_facturefourn)",
	),
	'bank'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid IN (SELECT rowid FROM ".MAIN_DB_PREFIX."bank WHERE datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."bank_url WHERE fk_bank IN (SELECT rowid FROM ".MAIN_DB_PREFIX."bank WHERE datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."bank WHERE datec < '__DATE__'",
	),
	'bankaccount'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."bank_account WHERE datec < '__DATE__'",
	),
	'invoice'=>array(
		'@payment',
		"DELETE FROM ".MAIN_DB_PREFIX."societe_remise_except where fk_facture_source IN (select rowid FROM ".MAIN_DB_PREFIX."facture where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."societe_remise_except where fk_facture IN (select rowid FROM ".MAIN_DB_PREFIX."facture where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."societe_remise_except where fk_facture_line IN (select rowid FROM ".MAIN_DB_PREFIX."facturedet as fd WHERE fd.fk_facture IN (select rowid from ".MAIN_DB_PREFIX."facture where datec < '__DATE__'))",
		"DELETE FROM ".MAIN_DB_PREFIX."facture_rec where datec < '__DATE__'",
		"DELETE FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture IN (select rowid FROM ".MAIN_DB_PREFIX."facture where datec < '__DATE__')",
		"UPDATE ".MAIN_DB_PREFIX."facture SET fk_facture_source = NULL WHERE fk_facture_source IN (select f2.rowid FROM (select * from ".MAIN_DB_PREFIX."facture) as f2 where f2.datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."facture where datec < '__DATE__'",
	),
	'accounting'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."accounting_bookkeeping where doc_date < '__DATE__'",
	),
	'proposal'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal IN (select rowid FROM ".MAIN_DB_PREFIX."propal where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."propal WHERE datec < '__DATE__'",
	),
	"supplier_proposal"=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."supplier_proposaldet WHERE fk_supplier_proposal IN (select rowid FROM ".MAIN_DB_PREFIX."propal where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."supplier_proposal where datec < '__DATE__'",
	),
	'order'=>array(
		'@shipment',
		"DELETE FROM ".MAIN_DB_PREFIX."commandedet WHERE fk_commande IN (select rowid FROM ".MAIN_DB_PREFIX."commande where date_creation < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."commande where date_creation < '__DATE__'",
	),
	'supplier_order'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseurdet WHERE fk_commande IN (select rowid FROM ".MAIN_DB_PREFIX."commande_fournisseur where date_creation < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseur where date_creation < '__DATE__'",
	),
	'supplier_invoice'=>array(
		'@supplier_payment',
		"DELETE FROM ".MAIN_DB_PREFIX."facture_fourn_det WHERE fk_facture_fourn IN (select rowid FROM ".MAIN_DB_PREFIX."facture_fourn where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."facture_fourn where datec < '__DATE__'",
	),
	'shipment'=>array(
		'@delivery',
		"DELETE FROM ".MAIN_DB_PREFIX."expeditiondet_batch WHERE fk_expeditiondet IN (select rowid FROM ".MAIN_DB_PREFIX."expeditiondet WHERE fk_expedition IN (select rowid FROM ".MAIN_DB_PREFIX."expedition where date_creation < '__DATE__'))",
		"DELETE FROM ".MAIN_DB_PREFIX."expeditiondet_extrafields WHERE fk_object IN (select rowid FROM ".MAIN_DB_PREFIX."expeditiondet WHERE fk_expedition IN (select rowid FROM ".MAIN_DB_PREFIX."expedition where date_creation < '__DATE__'))",
		"DELETE FROM ".MAIN_DB_PREFIX."expeditiondet WHERE fk_expedition IN (select rowid FROM ".MAIN_DB_PREFIX."expedition where date_creation < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."expedition_extrafields WHERE fk_object IN (select rowid FROM ".MAIN_DB_PREFIX."expedition where date_creation < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."expedition where date_creation < '__DATE__'",
	),
	'delivery'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."deliverydet WHERE fk_delivery IN (select rowid FROM ".MAIN_DB_PREFIX."delivery where date_creation < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."delivery where date_creation < '__DATE__'",
	),
	'contract'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."contratdet_extrafields WHERE fk_object IN (select rowid FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat IN (select rowid FROM ".MAIN_DB_PREFIX."contrat where datec < '__DATE__'))",
		"DELETE FROM ".MAIN_DB_PREFIX."contratdet WHERE fk_contrat IN (select rowid FROM ".MAIN_DB_PREFIX."contrat where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."contrat_extrafields WHERE fk_object IN (select rowid FROM ".MAIN_DB_PREFIX."contrat where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."contrat WHERE datec < '__DATE__'",
	),
	'intervention'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."fichinterdet WHERE fk_fichinter IN (select rowid FROM ".MAIN_DB_PREFIX."fichinter where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."fichinter where datec < '__DATE__'",
	),
	'stock'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."stock_mouvement WHERE datem < '__DATE__'",
	),
	'product'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_product IN (select rowid FROM ".MAIN_DB_PREFIX."product where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."product_lang WHERE fk_product IN (select rowid FROM ".MAIN_DB_PREFIX."product where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."product_price_by_qty WHERE fk_product_price IN (select rowid FROM ".MAIN_DB_PREFIX."product_price where fk_product IN (select rowid FROM ".MAIN_DB_PREFIX."product where datec < '__DATE__'))",
		"DELETE FROM ".MAIN_DB_PREFIX."product_price WHERE fk_product IN (select rowid FROM ".MAIN_DB_PREFIX."product where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price WHERE fk_product IN (select rowid FROM ".MAIN_DB_PREFIX."product where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."product_batch WHERE fk_product_stock IN (select rowid FROM ".MAIN_DB_PREFIX."product_stock where fk_product IN (select rowid FROM ".MAIN_DB_PREFIX."product where datec < '__DATE__'))",
		"DELETE FROM ".MAIN_DB_PREFIX."product_stock WHERE fk_product IN (select rowid FROM ".MAIN_DB_PREFIX."product where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."product_lot WHERE fk_product IN (select rowid FROM ".MAIN_DB_PREFIX."product where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."product where datec < '__DATE__'",
	),
	'project'=>array(
		// TODO set fk_project to null on all objects/tables that refer to project
		"DELETE FROM ".MAIN_DB_PREFIX."element_time WHERE elementtype = 'task' AND fk_element IN (select rowid FROM ".MAIN_DB_PREFIX."projet_task WHERE fk_projet IN (select rowid FROM ".MAIN_DB_PREFIX."projet where datec < '__DATE__'))",
		"DELETE FROM ".MAIN_DB_PREFIX."projet_task WHERE fk_projet IN (select rowid FROM ".MAIN_DB_PREFIX."projet where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."projet where datec < '__DATE__'",
	),
	'contact'=>array(
		"DELETE FROM ".MAIN_DB_PREFIX."categorie_contact WHERE fk_socpeople IN (select rowid FROM ".MAIN_DB_PREFIX."socpeople where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."socpeople where datec < '__DATE__'",
	),
	'thirdparty'=>array(
		'@contact',
		"DELETE FROM ".MAIN_DB_PREFIX."cabinetmed_cons WHERE fk_soc IN (select rowid FROM ".MAIN_DB_PREFIX."societe where datec < '__DATE__')",
		"UPDATE ".MAIN_DB_PREFIX."adherent SET fk_soc = NULL WHERE fk_soc IN (select rowid FROM ".MAIN_DB_PREFIX."societe where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."categorie_fournisseur WHERE fk_soc IN (select rowid FROM ".MAIN_DB_PREFIX."societe where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."categorie_societe WHERE fk_soc IN (select rowid FROM ".MAIN_DB_PREFIX."societe where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."societe_remise_except WHERE fk_soc IN (select rowid FROM ".MAIN_DB_PREFIX."societe where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."societe_rib WHERE fk_soc IN (select rowid FROM ".MAIN_DB_PREFIX."societe where datec < '__DATE__')",
		"DELETE FROM ".MAIN_DB_PREFIX."societe where datec < '__DATE__'",
	)
);


/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".implode(',', $argv));

$mode = $argv[1];
$option = $argv[2];
$date = $argv[3];

if (empty($mode) || ! in_array($mode, array('test','confirm'))) {
	print "Usage:  $script_file (test|confirm) (all|option) (all|YYYY-MM-DD) [dbtype dbhost dbuser dbpassword dbname dbport]\n";
	print "\n";
	print "option can be ".implode(',', array_keys($sqls))."\n";
	exit(-1);
}
if (empty($option)) {
	print "Usage:  $script_file (test|confirm) (all|option) (all|YYYY-MM-DD) [dbtype dbhost dbuser dbpassword dbname dbport]\n";
	print "\n";
	print "option must be defined with a value in list ".implode(',', array_keys($sqls))."\n";
	exit(-1);
}
if ($option != 'all') {
	$listofoptions=explode(',', $option);
	foreach ($listofoptions as $cursoroption) {
		if (! in_array($cursoroption, array_keys($sqls))) {
			print "Usage:  $script_file (test|confirm) (all|option) (all|YYYY-MM-DD) [dbtype dbhost dbuser dbpassword dbname dbport]\n";
			print "\n";
			print "option '".$cursoroption."' must be in list ".implode(',', array_keys($sqls))."\n";
			exit(-1);
		}
	}
}

if (empty($date) || (! preg_match('/\d\d\d\d\-\d\d\-\d\d$/', $date) && $date != 'all')) {
	print "Usage:  $script_file (test|confirm) (all|option) (all|YYYY-MM-DD) [dbtype dbhost dbuser dbpassword dbname dbport]\n";
	print "\n";
	print "date can be 'all' or 'YYYY-MM-DD' to delete record before YYYY-MM-DD\n";
	exit(-1);
}

if ($date == 'all') {
	$date = '2199-01-01';
}

// Replace database handler
if (!empty($argv[4])) {
	$db->close();
	unset($db);
	$db=getDoliDBInstance($argv[4], $argv[5], $argv[6], $argv[7], $argv[8], $argv[9]);
	$user=new User($db);
}

//var_dump($user->db->database_name);
$ret=$user->fetch('', 'admin');
if (! $ret > 0) {
	print 'An admin user with login "admin" must exists to use this script.'."\n";
	exit;
}
//$user->getrights();


print "Purge all data for this database:\n";
print "Before = ".$date."\n";
print "Server = ".$db->database_host."\n";
print "Database name = ".$db->database_name."\n";
print "Database port = ".$db->database_port."\n";
print "User = ".$db->database_user."\n";
print "\n";

if (! $confirmed) {
	print "Hit Enter to continue or CTRL+C to stop...\n";
	$input = trim(fgets(STDIN));
}


/**
 * Process sql requests of a family
 *
 * @param   string  $family     Name of family key of array $sqls
 * @param   string  $date       Date value
 * @return  int                 -1 if KO, 1 if OK
 */
function processfamily($family, $date)
{
	global $db, $sqls;

	$error=0;
	foreach ($sqls[$family] as $sql) {
		if (preg_match('/^@/', $sql)) {
			$newfamily=preg_replace('/@/', '', $sql);
			processfamily($newfamily, $date);
			continue;
		}

		$sql = preg_replace('/__DATE__/', $date, $sql);

		print "Run sql: ".$sql."\n";

		$resql=$db->query($sql);
		if (! $resql) {
			if ($db->errno() != 'DB_ERROR_NOSUCHTABLE') {
				$error++;
			}
		}

		if ($error) {
			print $db->lasterror();
			$error++;
			break;
		}
	}

	if ($error) {
		return -1;
	} else {
		return 1;
	}
}


$db->begin();

$listofoptions=explode(',', $option);
foreach ($listofoptions as $cursoroption) {
	$oldfamily='';
	foreach ($sqls as $family => $familysql) {
		if ($cursoroption && $cursoroption != 'all' && $cursoroption != $family) {
			continue;
		}

		if ($family != $oldfamily) {
			print "Process action for family ".$family."\n";
		}
		$oldfamily = $family;

		$result=processfamily($family, $date);
		if ($result < 0) {
			$error++;
			break;
		}
	}
}

if ($error || $mode != 'confirm') {
	print "\nRollback any changes.\n";
	$db->rollback();
} else {
	print "Commit all changes.\n";
	$db->commit();
}

$db->close();
