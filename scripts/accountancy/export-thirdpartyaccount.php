#!/usr/bin/env php
<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2014	   Florian Henry		<florian.henry@open-concept.pro>
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
 * \file 		htdocs/accountancy/admin/export-thirdpartyaccount.php
 * \ingroup		Accounting Expert
 * \brief		Page to detect empty accounting account
 */

require_once($path."../../htdocs/master.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load("companies");
$langs->load("compta");
$langs->load("main");
$langs->load("accountancy");

// Security check
if (!$user->admin)
    accessforbidden();

// Date range
$year = GETPOST("year");
if (empty($year)) {
	$year_current = strftime("%Y", dol_now());
	$month_current = strftime("%m", dol_now());
	$year_start = $year_current;
} else {
	$year_current = $year;
	$month_current = strftime("%m", dol_now());
	$year_start = $year;
}
$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

// Quarter
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$q = GETPOST("q") ? GETPOST("q") : 0;
	if ($q == 0) {
		// We define date_start and date_end
		$year_end = $year_start;
		$month_start = GETPOST("month") ? GETPOST("month") : ($conf->global->SOCIETE_FISCAL_MONTH_START ? ($conf->global->SOCIETE_FISCAL_MONTH_START) : 1);
		if (! GETPOST('month')) {
			if (! GETPOST("year") && $month_start > $month_current) {
				$year_start --;
				$year_end --;
			}
			$month_end = $month_start - 1;
			if ($month_end < 1)
				$month_end = 12;
			else
				$year_end ++;
		} else
			$month_end = $month_start;
		$date_start = dol_get_first_day($year_start, $month_start, false);
		$date_end = dol_get_last_day($year_end, $month_end, false);
	}
	if ($q == 1) {
		$date_start = dol_get_first_day($year_start, 1, false);
		$date_end = dol_get_last_day($year_start, 3, false);
	}
	if ($q == 2) {
		$date_start = dol_get_first_day($year_start, 4, false);
		$date_end = dol_get_last_day($year_start, 6, false);
	}
	if ($q == 3) {
		$date_start = dol_get_first_day($year_start, 7, false);
		$date_end = dol_get_last_day($year_start, 9, false);
	}
	if ($q == 4) {
		$date_start = dol_get_first_day($year_start, 10, false);
		$date_end = dol_get_last_day($year_start, 12, false);
	}
} else {
}

llxHeader();

$form = new Form($db);

$nomlink = '';
$periodlink = '';
$exportlink = '';

$nom = $langs->trans("ReportThirdParty");
$period = $form->select_date($date_start, 'date_start', 0, 0, 0, '', 1, 0, 1) . ' - ' . $form->select_date($date_end, 'date_end', 0, 0, 0, '', 1, 0, 1);
$description = $langs->trans("DescThirdPartyReport");
$builddate = time();

$moreparam=array('action' => '');
report_header($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, $moreparam);

print '<input type="button" class="button" style="float: right;" value="Export CSV" onclick="launch_export();" />';

print '
	<script type="text/javascript">
		function launch_export()
		{
		    $("div.fiche div.tabBar form input[name=\"action\"]").val("export_csv");
			$("div.fiche div.tabBar form input[type=\"submit\"]").click();
		    $("div.fiche div.tabBar form input[name=\"action\"]").val();
		}
</script>';

$sql = "(SELECT s.rowid, s.nom as name , s.address, s.zip , s.town, s.code_compta as compta , ";
$sql .= " s.fk_forme_juridique , s.fk_pays , s.phone , s.fax ,   f.datec , f.fk_soc , cp.label as country ";
$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
$sql .= ", " . MAIN_DB_PREFIX . "facture as f";
$sql .= ", " . MAIN_DB_PREFIX . "c_country as cp";
$sql .= " WHERE f.fk_soc = s.rowid";
$sql .= " AND s.fk_pays = cp.rowid";
if (! empty($date_start) && ! empty($date_end))
	$sql .= " AND f.datec >= '" . $db->idate($date_start) . "' AND f.datec <= '" . $db->idate($date_end) . "'";
$sql .= " AND f.entity = " . $conf->entity;
if ($socid)
	$sql .= " AND f.fk_soc = " . $socid;
$sql .= " GROUP BY name";
$sql .= ")";
$sql .= "UNION (SELECT s.rowid, s.nom as name , s.address, s.zip , s.town, s.code_compta_fournisseur as compta , ";
$sql .= " s.fk_forme_juridique , s.fk_pays , s.phone , s.fax ,   ff.datec , ff.fk_soc , cp.label as country ";
$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
$sql .= ", " . MAIN_DB_PREFIX . "facture_fourn as ff";
$sql .= ", " . MAIN_DB_PREFIX . "c_country as cp";
$sql .= " WHERE ff.fk_soc = s.rowid";
$sql .= " AND s.fk_pays = cp.rowid";
if (! empty($date_start) && ! empty($date_end))
	$sql .= " AND ff.datec >= '" . $db->idate($date_start) . "' AND ff.datec <= '" . $db->idate($date_end) . "'";
$sql .= " AND ff.entity = " . $conf->entity;
if ($socid)
	$sql .= " AND f.fk_soc = " . $socid;
$sql .= " GROUP BY name";
$sql .= ")";

$sql .= "ORDER BY name ASC";

dol_syslog('accountancy/admin/thirdpartyaccount.php:: $sql=' . $sql);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	// export csv
	if (GETPOST('action') == 'export_csv') {

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename=export_csv.csv');

		$obj = $db->fetch_object($resql);

		print '"' . $obj->compta . '",';
		print '"' . $obj->address . '",';
		print '"' . $obj->zip . '",';
		print '"' . $obj->town . '",';
		print '"' . $obj->country . '",';
		print '"' . $obj->phone . '",';
		print '"' . $obj->fax . '",';
		print "\n";
		$i ++;
	}

	/*
	 * View
	 */

	$thirdpartystatic = new Societe($db);

	print '<br><br>';

	print '<table class="noborder" width="100%">';
	print "</table>\n";
	print '</td><td valign="top" width="70%" class="notopnoleftnoright"></td>';
	print '</tr><tr><td colspan=2>';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td align="left">' . $langs->trans("ThirdParties") . '</td>';
	print '<td align="left">' . $langs->trans("AccountNumber") . '</td>';
	print '<td align="left">' . $langs->trans("RaisonSociale") . '</td>';
	print '<td align="left">' . $langs->trans("Address") . '</td>';
	print '<td align="left">' . $langs->trans("Zip") . '</td>';
	print '<td align="left">' . $langs->trans("Town") . '</td>';
	print '<td align="left">' . $langs->trans("Country") . '</td>';
	print '<td align="left">' . $langs->trans("Contact") . '</td>';
	print '<td align="left">' . $langs->trans("Phone") . '</td>';
	print '<td align="left">' . $langs->trans("Fax") . '</td></tr>';

	$var = True;

	while ( $obj = $db->fetch_object($resql) ) {

		$var = ! $var;

		print '<tr'. $bc[$var].'>';
		print '<td>';
		$thirdpartystatic->id = $obj->rowid;
		$thirdpartystatic->name = $obj->name;
		$thirdpartystatic->client = $obj->client;
		$thirdpartystatic->canvas = $obj->canvas;
		$thirdpartystatic->status = $obj->status;
		print $thirdpartystatic->getNomUrl(1);
		print '</td>';
		print '<td align="left">' . $obj->compta . '</td>' . "\n";
		print '<td align="left"></td>';
		print '<td align="left">' . $obj->address . '</td>';
		print '<td align="left">' . $obj->zip . '</td>';
		print '<td align="left">' . $obj->town . '</td>';
		print '<td align="left">' . $obj->country . '</td>';
		print '<td align="left"></td>';
		print '<td align="left">' . $obj->phone . '</td>';
		print '<td align="left">' . $obj->fax . '</td>';
		print "</tr>\n";

		$i ++;
	}

	print "</table>";
	$db->free($resql);
} else {
	dol_print_error($db);
}

llxFooter();
$db->close();
