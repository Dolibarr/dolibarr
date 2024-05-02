#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2005 Rodolphe Quiedeville 		<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2013 Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2013 Juanjo Menent 			<jmenent@2byte.es>
 * Copyright (C) 2024 Vincent de Grandpré 		<vincent@de-grandpre.quebec>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file scripts/invoices/recalculate_total_and_taxes.php
 * \ingroup facture
 * \brief Script to calculate invoice totals and taxes from line items when the totals are zero. Use case : after data imporattion with ETL.
 */

if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

require $path."../../htdocs/master.inc.php";

$langs->load('main');

// Global variables
$version = DOL_VERSION;
$error = 0;

/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".join(',', $argv));

$now = dol_now('tzserver');
$duration_value = isset($argv[3]) ? $argv[3] : 'none';
$duration_value2 = isset($argv[4]) ? $argv[4] : 'none';

$error = 0;

if (!empty($dolibarr_main_db_readonly)) {
	print "Error: instance in read-onyl mode\n";
	exit(-1);
}

$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture WHERE total_ht = 0";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	print "We found ".$num." factures qualified\n";
	dol_syslog("We found ".$num." factures qualified");

	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			$sql_calculs = "
				SELECT 
					SUM(fd.total_ht) as 'total_ht', 
					SUM(fd.total_tva) as 'total_tva',
					SUM(fd.total_localtax1) as 'localtax1',
					SUM(fd.total_localtax2) as 'localtax2',
					SUM(fd.total_ttc) as 'total_ttc'
				FROM
					".MAIN_DB_PREFIX."facturedet fd
				WHERE 
					fd.fk_facture = $obj->rowid";
			$ressql_calculs = $db->query($sql_calculs);
			while ($obj_calcul = $db->fetch_object($ressql_calculs)) {
				$sql_maj = "
					UPDATE ".MAIN_DB_PREFIX."facture 
					SET 
						total_ht = ".($obj_calcul->total_ht ? price2num($obj_calcul->total_ht, 'MT') : 0).", 
						total_tva = ".($obj_calcul->total_tva ? price2num($obj_calcul->total_tva, 'MT') : 0).", 
						localtax1 = ".($obj_calcul->localtax1 ? price2num($obj_calcul->localtax1, 'MT') : 0).", 
						localtax2 = ".($obj_calcul->localtax2 ? price2num($obj_calcul->localtax2, 'MT') : 0).", 
						total_ttc = ".($obj_calcul->total_ttc ? price2num($obj_calcul->total_ttc, 'MT') : 0)." 
					WHERE 
						rowid = $obj->rowid";
				print "Exécution du SQL : $sql_maj\n";
				dol_syslog("Exécution du SQL : $sql_maj");
				$db->query($sql_maj);
			}
			$i++;
		}
	} else {
		print "Pas de factures à traiter\n";
	}
	exit(0);
} else {
	dol_print_error($db);
	dol_syslog("calculate_total_and_taxes.php: Error");
	exit(-1);
}
