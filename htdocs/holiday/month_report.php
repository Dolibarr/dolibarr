<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      François Legastelois <flegastelois@teclib.com>
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
 *   	\file       htdocs/holiday/month_report.php
 *		\ingroup    holiday
 *		\brief      Monthly report of paid holiday.
 */

require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/common.inc.php';

// Protection if external user
if ($user->societe_id > 0) accessforbidden();


// Si l'utilisateur n'a pas le droit de lire cette page
if(!$user->rights->holiday->month_report) accessforbidden();



/*
 * View
 */

$html = new Form($db);
$htmlother = new FormOther($db);
$holidaystatic = new Holiday($db);

llxHeader(array(),$langs->trans('CPTitreMenu'));

$cp = new Holiday($db);

$month = GETPOST('month_start');
$year = GETPOST('year_start');

if(empty($month)) {
	$month = date('m');
}
if(empty($year)) {
	$year = date('Y');
}

$sql = "SELECT cp.rowid, cp.fk_user, cp.date_debut, cp.date_fin, cp.halfday";
$sql.= " FROM llx_holiday cp";
$sql.= " LEFT JOIN llx_user u ON cp.fk_user = u.rowid";
$sql.= " WHERE cp.statut = 3";	// Approved
// TODO Use BETWEEN instead of date_format
$sql.= " AND (date_format(cp.date_debut, '%Y-%m') = '$year-$month' OR date_format(cp.date_fin, '%Y-%m') = '$year-$month')";
$sql.= " ORDER BY u.lastname,cp.date_debut";

$result  = $db->query($sql);
$num = $db->num_rows($result);

print_fiche_titre($langs->trans('MenuReportMonth'));

print '<div class="tabBar">';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";

print $langs->trans('Month').': <input class="flat" type="text" size="1" maxlength="2" name="month_start" value="'.$month.'">&nbsp;';
$htmlother->select_year($year,'year_start',1,10,3);

print '<input type="submit" value="'.$langs->trans("Refresh").'" class="button" />';

print '</form>';

print '<br>';

$var=true;
print '<table class="noborder" width="40%;">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans('Ref').'</td>';
print '<td>'.$langs->trans('Employee').'</td>';
print '<td>'.$langs->trans('DateDebCP').'</td>';
print '<td>'.$langs->trans('DateFinCP').'</td>';
print '<td align="right">'.$langs->trans('nbJours').'</td>';
print '</tr>';

if($num == '0') {

	print '<tr class="pair">';
	print '<td colspan="5">'.$langs->trans('None').'</td>';
	print '</tr>';

} else {

	$langs->load('users');

	while ($holiday = $db->fetch_array($result))
	{
		$user = new User($db);
		$user->fetch($holiday['fk_user']);
		$var=!$var;

		$holidaystatic->id=$holiday['rowid'];
		$holidaystatic->ref=$holiday['rowid'];

		$start_date=$db->jdate($holiday['date_debut']);
		$end_date=$db->jdate($holiday['date_fin']);
		/*if(substr($holiday['date_debut'],5,2)==$month-1){
			$holiday['date_debut'] = date('Y-'.$month.'-01');
		}

		if(substr($holiday['date_fin'],5,2)==$month+1){
			$holiday['date_fin'] = date('Y-'.$month.'-t');
		}*/

		print '<tr '.$bc[$var].'>';
		print '<td>'.$holidaystatic->getNomUrl(1).'</td>';
		print '<td>'.$user->lastname.' '.$user->firstname.'</td>';
		print '<td>'.dol_print_date($start_date,'day');
		print '</td>';
		print '<td>'.dol_print_date($end_date,'day');
		print '</td>';
		print '<td align="right">';
		$nbopenedday=num_open_day($start_date, $end_date, 0, 1, $holiday['halfday']);
		print $nbopenedday;
		print '</td>';
		print '</tr>';
	}
}
print '</table>';
print '</div>';

// Fin de page
llxFooter();

$db->close();
?>
