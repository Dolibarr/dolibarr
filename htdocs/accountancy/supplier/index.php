<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon TOSSER			<simon@kornog-computing.com>
 * Copyright (C) 2013      Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014 Alexandre Spangaro	<alexandre.spangaro@gmail.com>
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
 */

/**
 * \file		accountingex/supplier/index.php
 * \ingroup	Accounting Expert
 * \brief		Page accueil ventilation
 */

// Dolibarr environment
$res = @include ("../main.inc.php");
if (! $res && file_exists("../main.inc.php"))
	$res = @include ("../main.inc.php");
if (! $res && file_exists("../../main.inc.php"))
	$res = @include ("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php"))
	$res = @include ("../../../main.inc.php");
if (! $res)
	die("Include of main fails");
	
	// Class
dol_include_once("/core/lib/date.lib.php");

// Langs
$langs->load("compta");
$langs->load("bills");
$langs->load("other");
$langs->load("main");
$langs->load("accountingex@accountingex");

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accountingex->access)
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
		$sql1 = "UPDATE " . MAIN_DB_PREFIX . "facture_fourn_det as fd";
		$sql1 .= " SET fd.fk_code_ventilation = accnt.rowid";
		$sql1 .= " FROM " . MAIN_DB_PREFIX . "product as p, " . MAIN_DB_PREFIX . "accountingaccount as accnt , " . MAIN_DB_PREFIX . "accounting_system as syst";
		$sql1 .= " WHERE fd.fk_product = p.rowid  AND accnt.fk_pcg_version = syst.pcg_version AND syst.rowid=" . $conf->global->CHARTOFACCOUNTS;
		$sql1 .= " AND accnt.active = 1 AND p.accountancy_code_buy=accnt.account_number";
		$sql1 .= " AND fd.fk_code_ventilation = 0";
	} else {
		$sql1 = "UPDATE " . MAIN_DB_PREFIX . "facture_fourn_det as fd, " . MAIN_DB_PREFIX . "product as p, " . MAIN_DB_PREFIX . "accountingaccount as accnt , " . MAIN_DB_PREFIX . "accounting_system as syst";
		$sql1 .= " SET fd.fk_code_ventilation = accnt.rowid";
		$sql1 .= " WHERE fd.fk_product = p.rowid AND accnt.fk_pcg_version = syst.pcg_version AND syst.rowid=" . $conf->global->CHARTOFACCOUNTS;
		$sql1 .= " AND accnt.active = 1 AND p.accountancy_code_buy=accnt.account_number";
		$sql1 .= " AND fd.fk_code_ventilation = 0";
	}
	
	$resql1 = $db->query($sql1);
	if (! $resql1) {
		$error ++;
		$db->rollback();
		setEventMessage($db->lasterror(), 'errors');
	} else {
		$db->commit();
		setEventMessage($langs->trans('Dispatched'), 'mesgs');
	}
}

/*
 * View
 */

llxHeader('', $langs->trans("SuppliersVentilation"));

$textprevyear = "<a href=\"index.php?year=" . ($year_current - 1) . "\">" . img_previous() . "</a>";
$textnextyear = " <a href=\"index.php?year=" . ($year_current + 1) . "\">" . img_next() . "</a>";

print_fiche_titre($langs->trans("VentilationComptableSupplier") . " " . $textprevyear . " " . $langs->trans("Year") . " " . $year_start . " " . $textnextyear);

print '<b>' . $langs->trans("DescVentilSupplier") . '</b>';
print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=validatehistory">' . $langs->trans("ValidateHistory") . '</a></div>';

$y = $year_current;

$var = true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td align="left">' . $langs->trans("Account") . '</td>';
print '<td align="left">' . $langs->trans("Intitule") . '</td>';
print '<td align="center">' . $langs->trans("JanuaryMin") . '</td>';
print '<td align="center">' . $langs->trans("FebruaryMin") . '</td>';
print '<td align="center">' . $langs->trans("MarchMin") . '</td>';
print '<td align="center">' . $langs->trans("AprilMin") . '</td>';
print '<td align="center">' . $langs->trans("MayMin") . '</td>';
print '<td align="center">' . $langs->trans("JuneMin") . '</td>';
print '<td align="center">' . $langs->trans("JulyMin") . '</td>';
print '<td align="center">' . $langs->trans("AugustMin") . '</td>';
print '<td align="center">' . $langs->trans("SeptemberMin") . '</td>';
print '<td align="center">' . $langs->trans("OctoberMin") . '</td>';
print '<td align="center">' . $langs->trans("NovemberMin") . '</td>';
print '<td align="center">' . $langs->trans("DecemberMin") . '</td>';
print '<td align="center"><b>' . $langs->trans("Total") . '</b></td></tr>';

$sql = "SELECT IF(aa.account_number IS NULL, 'Non pointe', aa.account_number) AS 'code comptable',";
$sql .= "  IF(aa.label IS NULL, 'Non pointe', aa.label) AS 'Intitulé',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=1,ffd.total_ht,0)),2) AS 'Janvier',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=2,ffd.total_ht,0)),2) AS 'Fevrier',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=3,ffd.total_ht,0)),2) AS 'Mars',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=4,ffd.total_ht,0)),2) AS 'Avril',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=5,ffd.total_ht,0)),2) AS 'Mai',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=6,ffd.total_ht,0)),2) AS 'Juin',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=7,ffd.total_ht,0)),2) AS 'Juillet',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=8,ffd.total_ht,0)),2) AS 'Aout',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=9,ffd.total_ht,0)),2) AS 'Septembre',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=10,ffd.total_ht,0)),2) AS 'Octobre',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=11,ffd.total_ht,0)),2) AS 'Novembre',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=12,ffd.total_ht,0)),2) AS 'Decembre',";
$sql .= "  ROUND(SUM(ffd.total_ht),2) as 'Total'";
$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn_det as ffd";
$sql .= "  LEFT JOIN " . MAIN_DB_PREFIX . "facture_fourn as ff ON ff.rowid = ffd.fk_facture_fourn";
$sql .= "  LEFT JOIN " . MAIN_DB_PREFIX . "accountingaccount as aa ON aa.rowid = ffd.fk_code_ventilation";
$sql .= " WHERE ff.datef >= '" . $db->idate(dol_get_first_day($y, 1, false)) . "'";
$sql .= "  AND ff.datef <= '" . $db->idate(dol_get_last_day($y, 12, false)) . "'";
$sql .= "  AND ff.fk_statut > 0 ";

if (! empty($conf->multicompany->enabled)) {
	$sql .= " AND ff.entity = '" . $conf->entity . "'";
}

$sql .= " GROUP BY ffd.fk_code_ventilation";

dol_syslog('/accountingex/supplier/index.php:: sql=' . $sql);
$resql = $db->query($sql);
if ($resql) {
	$i = 0;
	$num = $db->num_rows($resql);
	
	while ( $i < $num ) {
		
		$row = $db->fetch_row($resql);
		
		print '<tr><td>' . $row[0] . '</td>';
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
	print $db->lasterror(); // affiche la derniere erreur sql
}
print "</table>\n";

print "<br>\n";
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td width=150>' . $langs->trans("Total") . '</td>';
print '<td align="center">' . $langs->trans("JanuaryMin") . '</td>';
print '<td align="center">' . $langs->trans("FebruaryMin") . '</td>';
print '<td align="center">' . $langs->trans("MarchMin") . '</td>';
print '<td align="center">' . $langs->trans("AprilMin") . '</td>';
print '<td align="center">' . $langs->trans("MayMin") . '</td>';
print '<td align="center">' . $langs->trans("JuneMin") . '</td>';
print '<td align="center">' . $langs->trans("JulyMin") . '</td>';
print '<td align="center">' . $langs->trans("AugustMin") . '</td>';
print '<td align="center">' . $langs->trans("SeptemberMin") . '</td>';
print '<td align="center">' . $langs->trans("OctoberMin") . '</td>';
print '<td align="center">' . $langs->trans("NovemberMin") . '</td>';
print '<td align="center">' . $langs->trans("DecemberMin") . '</td>';
print '<td align="center"><b>' . $langs->trans("Total") . '</b></td></tr>';

$sql = "SELECT '" . $langs->trans("CAHTF") . "' AS 'Total',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=1,ffd.total_ht,0)),2) AS 'Janvier',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=2,ffd.total_ht,0)),2) AS 'Fevrier',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=3,ffd.total_ht,0)),2) AS 'Mars',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=4,ffd.total_ht,0)),2) AS 'Avril',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=5,ffd.total_ht,0)),2) AS 'Mai',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=6,ffd.total_ht,0)),2) AS 'Juin',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=7,ffd.total_ht,0)),2) AS 'Juillet',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=8,ffd.total_ht,0)),2) AS 'Aout',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=9,ffd.total_ht,0)),2) AS 'Septembre',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=10,ffd.total_ht,0)),2) AS 'Octobre',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=11,ffd.total_ht,0)),2) AS 'Novembre',";
$sql .= "  ROUND(SUM(IF(MONTH(ff.datef)=12,ffd.total_ht,0)),2) AS 'Decembre',";
$sql .= "  ROUND(SUM(ffd.total_ht),2) as 'Total'";
$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn_det as ffd";
$sql .= "  LEFT JOIN " . MAIN_DB_PREFIX . "facture_fourn as ff ON ff.rowid = ffd.fk_facture_fourn";
$sql .= " WHERE ff.datef >= '" . $db->idate(dol_get_first_day($y, 1, false)) . "'";
$sql .= "  AND ff.datef <= '" . $db->idate(dol_get_last_day($y, 12, false)) . "'";
$sql .= "  AND ff.fk_statut > 0 ";

if (! empty($conf->multicompany->enabled)) {
	$sql .= " AND ff.entity = '" . $conf->entity . "'";
}

dol_syslog('/accountingex/supplier/index.php:: sql=' . $sql);
$resql = $db->query($sql);
if ($resql) {
	$i = 0;
	$num = $db->num_rows($resql);
	
	while ( $i < $num ) {
		$row = $db->fetch_row($resql);
		
		print '<tr><td>' . $row[0] . '</td>';
		print '<td align="center">' . $row[1] . '</td>';
		print '<td align="center">' . price($row[2]) . '</td>';
		print '<td align="center">' . price($row[3]) . '</td>';
		print '<td align="center">' . price($row[4]) . '</td>';
		print '<td align="center">' . price($row[5]) . '</td>';
		print '<td align="center">' . price($row[6]) . '</td>';
		print '<td align="center">' . price($row[7]) . '</td>';
		print '<td align="center">' . price($row[8]) . '</td>';
		print '<td align="center">' . price($row[9]) . '</td>';
		print '<td align="center">' . price($row[10]) . '</td>';
		print '<td align="center">' . price($row[11]) . '</td>';
		print '<td align="center">' . price($row[12]) . '</td>';
		print '<td align="center"><b>' . price($row[13]) . '</b></td>';
		print '</tr>';
		
		$i ++;
	}
	
	$db->free($resql);
} else {
	print $db->lasterror(); // show last sql error
}
print "</table>\n";

llxFooter();
$db->close();