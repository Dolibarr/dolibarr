#!/usr/bin/env php
<?php
/* Copyright (C) 2016 Laurent Destailleur	<eldy@users.sourceforge.net>
 *
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
 * or see https://www.gnu.org/
 *
 * Get a distant dump file and load it into a mysql database
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit;
}

// Global variables
$error = 0;

$confirm = isset($argv[1]) ? $argv[1] : '';

// Include Dolibarr environment
$res = 0;
$reg = array();
if (!$res && file_exists($path."../../master.inc.php")) {
	$res = @include $path."../../master.inc.php";
}
if (!$res && file_exists($path."../../htdocs/master.inc.php")) {
	$res = @include $path."../../htdocs/master.inc.php";
}
if (!$res && file_exists("../master.inc.php")) {
	$res = @include "../master.inc.php";
}
if (!$res && file_exists("../../master.inc.php")) {
	$res = @include "../../master.inc.php";
}
if (!$res && file_exists("../../../master.inc.php")) {
	$res = @include "../../../master.inc.php";
}
if (!$res && preg_match('/\/nltechno([^\/]*)\//', $_SERVER["PHP_SELF"], $reg)) {
	$res = @include $path."../../../dolibarr".$reg[1]."/htdocs/master.inc.php"; // Used on dev env only
}
if (!$res && preg_match('/\/nltechno([^\/]*)\//', $_SERVER["PHP_SELF"], $reg)) {
	$res = @include "../../../dolibarr".$reg[1]."/htdocs/master.inc.php"; // Used on dev env only
}
if (!$res) {
	die("Failed to include master.inc.php file\n");
}
/**
 * @var DoliDB $db
 */
include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';


/*
 *	Main
 */

print "***** ".$script_file." ".$confirm." *****\n";
if (empty($confirm)) {
	print "Usage: $script_file confirm|confirmresetblockedlog\n";
	print "Return code: 0 if success, <>0 if error\n";
	exit(1);
}


$dolnow = dol_now();

// Current year
$tmp = dol_getdate($dolnow);

$year = 2010;					// Old year in demo
$lastyear = $tmp['year'] - 2;	// New year in demo

$tables = array(
	'propal' => array(0 => 'datep', 1 => 'fin_validite', 2 => 'date_valid', 3 => 'date_cloture'),
	'commande' => array(0 => 'date_commande', 1 => 'date_valid', 2 => 'date_cloture'),
	'facture' => array(0 => 'datec', 1 => 'datef', 2 => 'date_valid', 3 => 'date_lim_reglement'),
	'paiement' => array(0 => 'datep'),
	'bank' => array(0 => 'datev', 1 => 'dateo'),
	'commande_fournisseur' => array(0 => 'date_commande', 1 => 'date_valid', 3 => 'date_creation', 4 => 'date_approve', 5 => 'date_approve2', 6 => 'date_livraison'),
	'supplier_proposal' => array(0 => 'datec', 1 => 'date_valid', 2 => 'date_cloture'),
	'expensereport' => array(0 => 'date_debut', 1 => 'date_fin', 2 => 'date_create', 3 => 'date_valid', 4 => 'date_approve', 5 => 'date_refuse', 6 => 'date_cancel'),
	'holiday' => array(0 => 'date_debut', 1 => 'date_fin', 2 => 'date_create', 3 => 'date_valid', 5 => 'date_refuse', 6 => 'date_cancel'),
	'ticket' => array(0 => 'datec', 1 => 'date_read', 2 => 'date_close')
);


if ($confirm == 'confirm') {
	print "Update dates to current year for database name = ".$db->database_name."\n";

	// Upgrade dates from 2010 to current year - 2.
	while ($year <= $lastyear) {
		//$year=2021;
		$delta1 = ($lastyear - $year);
		$delta2 = ($lastyear - $year - 1);
		//$delta=-1;

		if ($delta1) {
			foreach ($tables as $tablekey => $tableval) {
				print "Correct ".$tablekey." for year ".$year." and move them to current year ".$lastyear." ";
				$sql = "select rowid from ".MAIN_DB_PREFIX.$tablekey." where ".$tableval[0]." between '".$year."-01-01' and '".$year."-12-31' and ".$tableval[0]." < DATE_ADD(NOW(), INTERVAL -1 YEAR)";
				//$sql="select rowid from ".MAIN_DB_PREFIX.$tablekey." where ".$tableval[0]." between '".$year."-01-01' and '".$year."-12-31' and ".$tableval[0]." > NOW()";
				$resql = $db->query($sql);
				if ($resql) {
					$num = $db->num_rows($resql);
					$i = 0;
					while ($i < $num) {
						$obj = $db->fetch_object($resql);
						if ($obj) {
							print ".";
							$sql2 = "UPDATE ".MAIN_DB_PREFIX.$tablekey." set ";
							$j = 0;
							foreach ($tableval as $field) {
								if ($j) {
									$sql2 .= ", ";
								}
								$sql2 .= $field." = ".$db->ifsql("DATE_ADD(".$field.", INTERVAL ".$delta1." YEAR) > NOW()", "DATE_ADD(".$field.", INTERVAL ".$delta2." YEAR)", "DATE_ADD(".$field.", INTERVAL ".$delta1." YEAR)");
								$j++;
							}
							$sql2 .= " WHERE rowid = ".$obj->rowid;
							//print $sql2."\n";
							$resql2 = $db->query($sql2);
							if (!$resql2) {
								dol_print_error($db);
							}
						}
						$i++;
					}
				} else {
					dol_print_error($db);
				}
				print "\n";
			}
		}

		$year++;
	}
}

if ($confirm == 'confirmresetblockedlog') {
	$year = $tmp['year'];			// Old year in demo
	$lastyear = $tmp['year'] - 2;	// New year in demo

	// Upgrade dates from current year to current year - 2.
	while ($year >= $lastyear) {
		//$year=2021;
		$delta1 = ($lastyear - $year);			// negative value
		$delta2 = ($lastyear - $year - 1);		// negative value
		//$delta=-1;

		if ($delta1) {
			foreach ($tables as $tablekey => $tableval) {
				print "Correct ".$tablekey." for year ".$year." and move them to current year ".$lastyear." ";
				$sql = "select rowid from ".MAIN_DB_PREFIX.$tablekey." where ".$tableval[0]." between '".$year."-01-01' and '".$year."-12-31'";
				$resql = $db->query($sql);
				if ($resql) {
					$num = $db->num_rows($resql);
					$i = 0;
					while ($i < $num) {
						$obj = $db->fetch_object($resql);
						if ($obj) {
							print ".";
							$sql2 = "UPDATE ".MAIN_DB_PREFIX.$tablekey." set ";
							$j = 0;
							foreach ($tableval as $field) {
								if ($j) {
									$sql2 .= ", ";
								}
								$sql2 .= $field." = DATE_ADD(".$field.", INTERVAL ".$delta1." YEAR)";
								$j++;
							}
							$sql2 .= " WHERE rowid = ".$obj->rowid;
							//print $sql2."\n";
							$resql2 = $db->query($sql2);
							if (!$resql2) {
								dol_print_error($db);
							}
						}
						$i++;
					}
				} else {
					dol_print_error($db);
				}
				print "\n";
			}
		}

		$year--;
	}


	$sql = "CREATE TABLE tmp_delete (SELECT pf.fk_paiement FROM llx_paiement_facture as pf WHERE pf.fk_facture IN (SELECT f.rowid FROM llx_facture as f WHERE f.datef < '2024-12-31'))";
	print $sql;
	print "\n";
	$db->query($sql);

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."paiement_facture WHERE fk_paiement IN (SELECT fk_paiement FROM tmp_delete)";
	print $sql;
	print "\n";
	$db->query($sql);

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."paiement WHERE rowid IN (SELECT fk_paiement FROM tmp_delete)";
	print $sql;
	print "\n";
	$db->query($sql);

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet as fd WHERE fd.fk_facture IN (SELECT rowid FROM ".MAIN_DB_PREFIX."facture WHERE datef < '".$lastyear."-12-31')";
	print $sql;
	print "\n";
	$db->query($sql);

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."facture WHERE datef < '".$lastyear."-12-31'";
	print $sql;
	print "\n";
	$db->query($sql);

	$sql = "DROP TABLE tmp_delete";
	print $sql;
	print "\n";
	$db->query($sql);


	$sql = "UPDATE ".MAIN_DB_PREFIX."facture SET datef = datec";
	print $sql;
	print "\n";
	$db->query($sql);

	$sql = "UPDATE ".MAIN_DB_PREFIX."paiement as p SET datep = (SELECT datef FROM ".MAIN_DB_PREFIX."facture as f WHERE f.rowid = (SELECT fk_facture FROM ".MAIN_DB_PREFIX."paiement_facture as pf WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = f.rowid) LIMIT 1)";
	print $sql;
	print "\n";
	$db->query($sql);


	$sql = "DELETE FROM ".MAIN_DB_PREFIX."blockedlog";
	print $sql;
	print "\n";
	$db->query($sql);

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'MAIN_FIRST_REGISTRATION_OK_DATE'";
	print $sql;
	print "\n";
	$db->query($sql);


	/*
	// Delete corrupted record no more used that still exists in demo image but can't exist in a production env
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."blockedlog WHERE action LIKE 'PAYMENT_VARIOUS_%'";
	print $sql;
	print "\n";
	$db->query($sql);

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."blockedlog WHERE rowid < 199";
	print $sql;
	print "\n";
	$db->query($sql);

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."blockedlog WHERE action LIKE 'MODULE_RESET'";
	print $sql;
	print "\n";
	$db->query($sql);

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."blockedlog";
	print $sql;
	print "\n";
	$db->query($sql);

	$sql = "UPDATE ".MAIN_DB_PREFIX."blockedlog SET date_creation = tms WHERE date_creation <> tms";
	print $sql;
	print "\n";

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."blockedlog WHERE date_creation > '".dol_print_date($dolnow, 'day')."'";
	print $sql;
	print "\n";
	$db->query($sql);

	$sql = "UPDATE ".MAIN_DB_PREFIX."blockedlog SET debuginfo = NULL WHERE debuginfo IS NOT NULL";
	print $sql;
	print "\n";
	$db->query($sql);
	*/
}


print "\n";

exit(0);
