<?php
/* Copyright (C) 2013      Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2015 Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry  <jfefe@aternatik.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * \file htdocs/accountancy/customer/index.php
 * \ingroup Accounting Expert
 * \brief Home customer ventilation
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';

// Langs
$langs->load("compta");
$langs->load("bills");
$langs->load("other");
$langs->load("main");
$langs->load("accountancy");

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accounting->ventilation->read)
	accessforbidden();
	
	// Filter
$year = $_GET["year"];
if ($year == 0) {
	$year_current = strftime("%Y", time());
	$year_start = $year_current;
} else {
	$year_current = $year;
	$year_start = $year;
}

// Validate History
$action = GETPOST('action');
if ($action == 'validatehistory') {
	
	$error = 0;
	$db->begin();
	
	if ($db->type == 'pgsql') {
		$sql1 = "UPDATE " . MAIN_DB_PREFIX . "facturedet as fd";
		$sql1 .= " SET fd.fk_code_ventilation = accnt.rowid";
		$sql1 .= " FROM " . MAIN_DB_PREFIX . "product as p, " . MAIN_DB_PREFIX . "accounting_account as accnt , " . MAIN_DB_PREFIX . "accounting_system as syst";
		$sql1 .= " WHERE fd.fk_product = p.rowid  AND accnt.fk_pcg_version = syst.pcg_version AND syst.rowid=" . $conf->global->CHARTOFACCOUNTS;
		$sql1 .= " AND accnt.active = 1 AND p.accountancy_code_sell=accnt.account_number";
		$sql1 .= " AND fd.fk_code_ventilation = 0";
	} else {
		$sql1 = "UPDATE " . MAIN_DB_PREFIX . "facturedet as fd, " . MAIN_DB_PREFIX . "product as p, " . MAIN_DB_PREFIX . "accounting_account as accnt , " . MAIN_DB_PREFIX . "accounting_system as syst";
		$sql1 .= " SET fd.fk_code_ventilation = accnt.rowid";
		$sql1 .= " WHERE fd.fk_product = p.rowid  AND accnt.fk_pcg_version = syst.pcg_version AND syst.rowid=" . $conf->global->CHARTOFACCOUNTS;
		$sql1 .= " AND accnt.active = 1 AND p.accountancy_code_sell=accnt.account_number";
		$sql1 .= " AND fd.fk_code_ventilation = 0";
	}
	
	dol_syslog("htdocs/accountancy/customer/index.php sql=" . $sql, LOG_DEBUG);
	
	$resql1 = $db->query($sql1);
	if (! $resql1) {
		$error ++;
		$db->rollback();
		setEventMessages($db->lasterror(), null, 'errors');
	} else {
		$db->commit();
		setEventMessages($langs->trans('Dispatched'), null, 'mesgs');
	}
} elseif ($action == 'fixaccountancycode') {
	$error = 0;
	$db->begin();
	
	$sql1 = "UPDATE " . MAIN_DB_PREFIX . "facturedet as fd";
	$sql1 .= " SET fd.fk_code_ventilation = 0";
	$sql1 .= ' WHERE fd.fk_code_ventilation NOT IN ';
	$sql1 .= '	(SELECT accnt.rowid ';
	$sql1 .= '	FROM ' . MAIN_DB_PREFIX . 'accountingaccount as accnt';
	$sql1 .= '	INNER JOIN ' . MAIN_DB_PREFIX . 'accounting_system as syst';
	$sql1 .= '	ON accnt.fk_pcg_version = syst.pcg_version AND syst.rowid=' . $conf->global->CHARTOFACCOUNTS . ')';
	
	dol_syslog("htdocs/accountancy/customer/index.php fixaccountancycode", LOG_DEBUG);
	
	$resql1 = $db->query($sql1);
	if (! $resql1) {
		$error ++;
		$db->rollback();
		setEventMessage($db->lasterror(), 'errors');
	} else {
		$db->commit();
		setEventMessage($langs->trans('Done'), 'mesgs');
	}
} elseif ($action == 'cleanaccountancycode') {
	$error = 0;
	$db->begin();
	
	$sql1 = "UPDATE " . MAIN_DB_PREFIX . "facturedet as fd";
	$sql1 .= " SET fd.fk_code_ventilation = 0";
	$sql1 .= " WHERE fd.fk_facture IN ( SELECT f.rowid FROM " . MAIN_DB_PREFIX . "facture as f";
	$sql1 .= " WHERE f.datef >= '" . $db->idate(dol_get_first_day($year_current, 1, false)) . "'";
	$sql1 .= "  AND f.datef <= '" . $db->idate(dol_get_last_day($year_current, 12, false)) . "')";
	
	dol_syslog("htdocs/accountancy/customer/index.php fixaccountancycode", LOG_DEBUG);
	
	$resql1 = $db->query($sql1);
	if (! $resql1) {
		$error ++;
		$db->rollback();
		setEventMessage($db->lasterror(), 'errors');
	} else {
		$db->commit();
		setEventMessage($langs->trans('Done'), 'mesgs');
	}
}

/*
 * View
 */
llxHeader('', $langs->trans("CustomersVentilation"));

$textprevyear = '<a href="' . $_SERVER["PHP_SELF"] . '?year=' . ($year_current - 1) . '">' . img_previous() . '</a>';
$textnextyear = '&nbsp;<a href="' . $_SERVER["PHP_SELF"] . '?year=' . ($year_current + 1) . '">' . img_next() . '</a>';

print load_fiche_titre($langs->trans("CustomersVentilation") . " " . $textprevyear . " " . $langs->trans("Year") . " " . $year_start . " " . $textnextyear);

print '<b>' . $langs->trans("DescVentilCustomer") . '</b>';
print '<div class="inline-block divButAction">';
print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?year=' . $year_current . '&action=validatehistory">' . $langs->trans("ValidateHistory") . '</a>';
print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?year=' . $year_current . '&action=fixaccountancycode">' . $langs->trans("CleanFixHistory", $year_current) . '</a>';
print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?year=' . $year_current . '&action=cleanaccountancycode">' . $langs->trans("CleanHistory", $year_current) . '</a>';
print '</div>';

$sql = "SELECT count(*) FROM " . MAIN_DB_PREFIX . "facturedet as fd";
$sql .= " , " . MAIN_DB_PREFIX . "facture as f";
$sql .= " WHERE fd.fk_code_ventilation = 0";
$sql .= " AND f.rowid = fd.fk_facture AND f.fk_statut = 1;";

dol_syslog("htdocs/accountancy/customer/index.php sql=" . $sql, LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$row = $db->fetch_row($result);
	$nbfac = $row[0];
	$db->free($result);
}

$y = $year_current;

$var = true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td width="200">' . $langs->trans("Account") . '</td>';
print '<td width="200" align="left">' . $langs->trans("Label") . '</td>';
print '<td width="60" align="center">' . $langs->trans("JanuaryMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("FebruaryMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("MarchMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("AprilMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("MayMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("JuneMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("JulyMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("AugustMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("SeptemberMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("OctoberMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("NovemberMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("DecemberMin") . '</td>';
print '<td width="60" align="center"><b>' . $langs->trans("Total") . '</b></td></tr>';

$sql = "SELECT IF(aa.account_number IS NULL, 'Non pointe', aa.account_number) AS 'code comptable',";
$sql .= "  IF(aa.label IS NULL, 'Non pointe', aa.label) AS 'Intitulé',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=1,fd.total_ht,0)),2) AS 'Janvier',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=2,fd.total_ht,0)),2) AS 'Fevrier',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=3,fd.total_ht,0)),2) AS 'Mars',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=4,fd.total_ht,0)),2) AS 'Avril',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=5,fd.total_ht,0)),2) AS 'Mai',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=6,fd.total_ht,0)),2) AS 'Juin',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=7,fd.total_ht,0)),2) AS 'Juillet',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=8,fd.total_ht,0)),2) AS 'Aout',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=9,fd.total_ht,0)),2) AS 'Septembre',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=10,fd.total_ht,0)),2) AS 'Octobre',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=11,fd.total_ht,0)),2) AS 'Novembre',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=12,fd.total_ht,0)),2) AS 'Decembre',";
$sql .= "  ROUND(SUM(fd.total_ht),2) as 'Total'";
$sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as fd";
$sql .= "  LEFT JOIN " . MAIN_DB_PREFIX . "facture as f ON f.rowid = fd.fk_facture";
$sql .= "  LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = fd.fk_code_ventilation";
$sql .= " WHERE f.datef >= '" . $db->idate(dol_get_first_day($y, 1, false)) . "'";
$sql .= "  AND f.datef <= '" . $db->idate(dol_get_last_day($y, 12, false)) . "'";

if (! empty($conf->multicompany->enabled)) {
	$sql .= " AND f.entity IN (" . getEntity("facture", 1) . ")";
}

$sql .= " GROUP BY fd.fk_code_ventilation";

dol_syslog("htdocs/accountancy/customer/index.php sql=" . $sql, LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$i = 0;
	$num = $db->num_rows($resql);
	
	while ( $i < $num ) {
		$row = $db->fetch_row($resql);
		$var = ! $var;
		print '<tr ' . $bc[$var] . '><td>' . length_accountg($row[0]) . '</td>';
		print '<td align="left">' . $row[1] . '</td>';
		print '<td align="right">' . price($row[2]) . '</td>';
		print '<td align="right">' . price($row[3]) . '</td>';
		print '<td align="right">' . price($row[4]) . '</td>';
		print '<td align="right">' . price($row[5]) . '</td>';
		print '<td align="right">' . price($row[6]) . '</td>';
		print '<td align="right">' . price($row[7]) . '</td>';
		print '<td align="right">' . price($row[8]) . '</td>';
		print '<td align="right">' . price($row[9]) . '</td>';
		print '<td align="right">' . price($row[10]) . '</td>';
		print '<td align="right">' . price($row[11]) . '</td>';
		print '<td align="right">' . price($row[12]) . '</td>';
		print '<td align="right">' . price($row[13]) . '</td>';
		print '<td align="right"><b>' . price($row[14]) . '</b></td>';
		print '</tr>';
		$i ++;
	}
	$db->free($resql);
} else {
	print $db->lasterror(); // Show last sql error
}
print "</table>\n";

print "<br>\n";
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td width="400" align="left">' . $langs->trans("TotalVente") . '</td>';
print '<td width="60" align="center">' . $langs->trans("JanuaryMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("FebruaryMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("MarchMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("AprilMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("MayMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("JuneMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("JulyMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("AugustMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("SeptemberMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("OctoberMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("NovemberMin") . '</td>';
print '<td width="60" align="center">' . $langs->trans("DecemberMin") . '</td>';
print '<td width="60" align="center"><b>' . $langs->trans("Total") . '</b></td></tr>';

$sql = "SELECT '" . $langs->trans("TotalVente") . "' AS 'Total',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=1,fd.total_ht,0)),2) AS 'Janvier',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=2,fd.total_ht,0)),2) AS 'Fevrier',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=3,fd.total_ht,0)),2) AS 'Mars',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=4,fd.total_ht,0)),2) AS 'Avril',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=5,fd.total_ht,0)),2) AS 'Mai',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=6,fd.total_ht,0)),2) AS 'Juin',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=7,fd.total_ht,0)),2) AS 'Juillet',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=8,fd.total_ht,0)),2) AS 'Aout',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=9,fd.total_ht,0)),2) AS 'Septembre',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=10,fd.total_ht,0)),2) AS 'Octobre',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=11,fd.total_ht,0)),2) AS 'Novembre',";
$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=12,fd.total_ht,0)),2) AS 'Decembre',";
$sql .= "  ROUND(SUM(fd.total_ht),2) as 'Total'";
$sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as fd";
$sql .= "  LEFT JOIN " . MAIN_DB_PREFIX . "facture as f ON f.rowid = fd.fk_facture";
$sql .= " WHERE f.datef >= '" . $db->idate(dol_get_first_day($y, 1, false)) . "'";
$sql .= "  AND f.datef <= '" . $db->idate(dol_get_last_day($y, 12, false)) . "'";

if (! empty($conf->multicompany->enabled)) {
	$sql .= " AND f.entity IN (" . getEntity("facture", 1) . ")";
}

dol_syslog('htdocs/accountancy/customer/index.php:: $sql=' . $sql);
$resql = $db->query($sql);
if ($resql) {
	$i = 0;
	$num = $db->num_rows($resql);
	
	while ( $i < $num ) {
		$row = $db->fetch_row($resql);
		
		print '<tr><td>' . $row[0] . '</td>';
		print '<td align="right">' . price($row[1]) . '</td>';
		print '<td align="right">' . price($row[2]) . '</td>';
		print '<td align="right">' . price($row[3]) . '</td>';
		print '<td align="right">' . price($row[4]) . '</td>';
		print '<td align="right">' . price($row[5]) . '</td>';
		print '<td align="right">' . price($row[6]) . '</td>';
		print '<td align="right">' . price($row[7]) . '</td>';
		print '<td align="right">' . price($row[8]) . '</td>';
		print '<td align="right">' . price($row[9]) . '</td>';
		print '<td align="right">' . price($row[10]) . '</td>';
		print '<td align="right">' . price($row[11]) . '</td>';
		print '<td align="right">' . price($row[12]) . '</td>';
		print '<td align="right"><b>' . price($row[13]) . '</b></td>';
		print '</tr>';
		$i ++;
	}
	$db->free($resql);
} else {
	print $db->lasterror(); // Show last sql error
}
print "</table>\n";

if (! empty($conf->margin->enabled)) {
	print "<br>\n";
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td width="400">' . $langs->trans("TotalMarge") . '</td>';
	print '<td width="60" align="center">' . $langs->trans("JanuaryMin") . '</td>';
	print '<td width="60" align="center">' . $langs->trans("FebruaryMin") . '</td>';
	print '<td width="60" align="center">' . $langs->trans("MarchMin") . '</td>';
	print '<td width="60" align="center">' . $langs->trans("AprilMin") . '</td>';
	print '<td width="60" align="center">' . $langs->trans("MayMin") . '</td>';
	print '<td width="60" align="center">' . $langs->trans("JuneMin") . '</td>';
	print '<td width="60" align="center">' . $langs->trans("JulyMin") . '</td>';
	print '<td width="60" align="center">' . $langs->trans("AugustMin") . '</td>';
	print '<td width="60" align="center">' . $langs->trans("SeptemberMin") . '</td>';
	print '<td width="60" align="center">' . $langs->trans("OctoberMin") . '</td>';
	print '<td width="60" align="center">' . $langs->trans("NovemberMin") . '</td>';
	print '<td width="60" align="center">' . $langs->trans("DecemberMin") . '</td>';
	print '<td width="60" align="center"><b>' . $langs->trans("Total") . '</b></td></tr>';
	
	$sql = "SELECT '" . $langs->trans("Vide") . "' AS 'Marge',";
	$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=1,(fd.total_ht-(fd.qty * fd.buy_price_ht)),0)),2) AS 'Janvier',";
	$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=2,(fd.total_ht-(fd.qty * fd.buy_price_ht)),0)),2) AS 'Fevrier',";
	$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=3,(fd.total_ht-(fd.qty * fd.buy_price_ht)),0)),2) AS 'Mars',";
	$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=4,(fd.total_ht-(fd.qty * fd.buy_price_ht)),0)),2) AS 'Avril',";
	$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=5,(fd.total_ht-(fd.qty * fd.buy_price_ht)),0)),2) AS 'Mai',";
	$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=6,(fd.total_ht-(fd.qty * fd.buy_price_ht)),0)),2) AS 'Juin',";
	$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=7,(fd.total_ht-(fd.qty * fd.buy_price_ht)),0)),2) AS 'Juillet',";
	$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=8,(fd.total_ht-(fd.qty * fd.buy_price_ht)),0)),2) AS 'Aout',";
	$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=9,(fd.total_ht-(fd.qty * fd.buy_price_ht)),0)),2) AS 'Septembre',";
	$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=10,(fd.total_ht-(fd.qty * fd.buy_price_ht)),0)),2) AS 'Octobre',";
	$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=11,(fd.total_ht-(fd.qty * fd.buy_price_ht)),0)),2) AS 'Novembre',";
	$sql .= "  ROUND(SUM(IF(MONTH(f.datef)=12,(fd.total_ht-(fd.qty * fd.buy_price_ht)),0)),2) AS 'Decembre',";
	$sql .= "  ROUND(SUM((fd.total_ht-(fd.qty * fd.buy_price_ht))),2) as 'Total'";
	$sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as fd";
	$sql .= "  LEFT JOIN " . MAIN_DB_PREFIX . "facture as f ON f.rowid = fd.fk_facture";
	$sql .= " WHERE f.datef >= '" . $db->idate(dol_get_first_day($y, 1, false)) . "'";
	$sql .= "  AND f.datef <= '" . $db->idate(dol_get_last_day($y, 12, false)) . "'";
	
	if (! empty($conf->multicompany->enabled)) {
		$sql .= " AND f.entity IN (" . getEntity("facture", 1) . ")";
	}
	
	dol_syslog('htdocs/accountancy/customer/index.php:: $sql=' . $sql);
	$resql = $db->query($sql);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);
		
		while ( $i < $num ) {
			$row = $db->fetch_row($resql);
			
			print '<tr><td>' . $row[0] . '</td>';
			print '<td align="right">' . price($row[1]) . '</td>';
			print '<td align="right">' . price($row[2]) . '</td>';
			print '<td align="right">' . price($row[3]) . '</td>';
			print '<td align="right">' . price($row[4]) . '</td>';
			print '<td align="right">' . price($row[5]) . '</td>';
			print '<td align="right">' . price($row[6]) . '</td>';
			print '<td align="right">' . price($row[7]) . '</td>';
			print '<td align="right">' . price($row[8]) . '</td>';
			print '<td align="right">' . price($row[9]) . '</td>';
			print '<td align="right">' . price($row[10]) . '</td>';
			print '<td align="right">' . price($row[11]) . '</td>';
			print '<td align="right">' . price($row[12]) . '</td>';
			print '<td align="right"><b>' . price($row[13]) . '</b></td>';
			print '</tr>';
			$i ++;
		}
		$db->free($resql);
	} else {
		print $db->lasterror(); // Show last sql error
	}
	print "</table>\n";
}
print "</table>\n";
print '</td></tr></table>';

llxFooter();
$db->close();