<?php
/* Copyright (C) 2007-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2011       François Legastelois    <flegastelois@teclib.com>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *      \file       month_report.php
 *      \ingroup    holiday
 *      \brief      Monthly report of leave requests.
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array("holiday"));

// Security check
$socid=0;
if ($user->societe_id > 0)	// Protection if external user
{
	//$socid = $user->societe_id;
	accessforbidden();
}
$result = restrictedArea($user, 'holiday', $id, '');



/*
 * View
 */

$holidaystatic = new Holiday($db);

$listhalfday=array('morning'=>$langs->trans("Morning"),"afternoon"=>$langs->trans("Afternoon"));


llxHeader('', $langs->trans('CPTitreMenu'));

print load_fiche_titre($langs->trans('MenuReportMonth'));

$html = new Form($db);
$formother = new FormOther($db);


// Selection filter
print '<div class="tabBar">';

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">' . "\n";

$search_month = GETPOST("remonth", 'int')?GETPOST("remonth", 'int'):date("m", time());
$search_year = GETPOST("reyear", 'int')?GETPOST("reyear", 'int'):date("Y", time());

$month_year = sprintf("%02d", $search_month).'-'.sprintf("%04d", $search_year);
$year_month = sprintf("%04d", $search_year).'-'.sprintf("%02d", $search_month);

print $formother->select_month($search_month, 'remonth');

print $formother->select_year($search_year, 'reyear');

print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Search")).'" />';

print '</form>';


$sql = "SELECT cp.rowid, cp.fk_user, cp.date_debut, cp.date_fin, ct.label, cp.description, cp.halfday";
$sql .= " FROM ".MAIN_DB_PREFIX."holiday cp";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user u ON cp.fk_user = u.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_holiday_types ct ON cp.fk_type = ct.rowid";
$sql .= " WHERE cp.rowid > 0";
$sql .= " AND cp.statut = 3";		// Approved
$sql .= " AND (date_format(cp.date_debut, '%Y-%m') = '".$db->escape($year_month)."' OR date_format(cp.date_fin, '%Y-%m') = '".$db->escape($year_month)."')";
$sql .= " ORDER BY u.lastname, cp.date_debut";

$resql = $db->query($sql);
if (empty($resql))
{
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

print '</div>';


print '<br>';

print '<div class="div-table-responsive">';
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Ref') . '</td>';
print '<td>' . $langs->trans('Employee') . '</td>';
print '<td>' . $langs->trans('Type') . '</td>';
print '<td class="center">' . $langs->trans('DateDebCP') . '</td>';
print '<td class="center">' . $langs->trans('DateFinCP') . '</td>';
print '<td class="right">' . $langs->trans('NbUseDaysCPShort') . '</td>';
print '<td class="center">' . $langs->trans('DateStartInMonth') . '</td>';
print '<td class="center">' . $langs->trans('DateEndInMonth') . '</td>';
print '<td class="right">' . $langs->trans('NbUseDaysCPShortInMonth') . '</td>';
print '<td class="maxwidth300">' . $langs->trans('DescCP') . '</td>';
print '</tr>';

if ($num == 0)
{
   print '<tr><td colspan="10" class="opacitymedium">'.$langs->trans('None').'</td></tr>';
}
else
{
   while ($obj = $db->fetch_object($resql))
   {
      $user = new User($db);
      $user->fetch($obj->fk_user);

      $date_start = $db->jdate($obj->date_debut, true);
      $date_end = $db->jdate($obj->date_fin, true);

      $tmpstart = dol_getdate($date_start);
      $tmpend = dol_getdate($date_end);

      $starthalfday=($obj->halfday == -1 || $obj->halfday == 2)?'afternoon':'morning';
      $endhalfday=($obj->halfday == 1 || $obj->halfday == 2)?'morning':'afternoon';

      $halfdayinmonth = $obj->halfday;
      $starthalfdayinmonth = $starthalfday;
      $endhalfdayinmonth = $endhalfday;

      //0:Full days, 2:Start afternoon end morning, -1:Start afternoon end afternoon, 1:Start morning end morning

      // Set date_start_gmt and date_end_gmt that are date to show for the selected month
      $date_start_inmonth = $db->jdate($obj->date_debut, true);
      $date_end_inmonth = $db->jdate($obj->date_fin, true);
      if ($tmpstart['year'] < $search_year || $tmpstart['mon'] < $search_month)
      {
      	$date_start_inmonth = dol_get_first_day($search_year, $search_month, true);
      	$starthalfdayinmonth = 'morning';
      	if ($halfdayinmonth ==  2) $halfdayinmonth=1;
      	if ($halfdayinmonth == -1) $halfdayinmonth=0;
      }
      if ($tmpend['year'] > $search_year || $tmpend['mon'] > $search_month)
      {
      	$date_end_inmonth = dol_get_last_day($search_year, $search_month, true) - ((24 * 3600) - 1);
      	$endhalfdayinmonth = 'afternoon';
      	if ($halfdayinmonth ==  2) $halfdayinmonth=-1;
      	if ($halfdayinmonth ==  1) $halfdayinmonth=0;
      }

      // Leave request
      $holidaystatic->id=$obj->rowid;
      $holidaystatic->ref=$obj->rowid;

      print '<tr class="oddeven">';
      	 print '<td>';
      	 print $holidaystatic->getNomUrl(1, 1);
      	 print '</td>';
         print '<td>' . $user->getFullName($langs) . '</td>';
         print '<td>' . $obj->label . '</td>';
         print '<td class="center">' . dol_print_date($db->jdate($obj->date_debut), 'day');
         print ' <span class="opacitymedium">('.$langs->trans($listhalfday[$starthalfday]).')</span>';
         print '</td>';
         print '<td class="center">' . dol_print_date($db->jdate($obj->date_fin), 'day');
         print ' <span class="opacitymedium">('.$langs->trans($listhalfday[$endhalfday]).')</span>';
         print '</td>';
         print '<td class="right">' . num_open_day($date_start, $date_end, 0, 1, $obj->halfday) . '</td>';
         print '<td class="center">' . dol_print_date($date_start_inmonth, 'day');
         print ' <span class="opacitymedium">('.$langs->trans($listhalfday[$starthalfdayinmonth]).')</span>';
         print '</td>';
         print '<td class="center">' . dol_print_date($date_end_inmonth, 'day');
         print ' <span class="opacitymedium">('.$langs->trans($listhalfday[$endhalfdayinmonth]).')</span>';
         print '</td>';
         print '<td class="right">' . num_open_day($date_start_inmonth, $date_end_inmonth, 0, 1, $halfdayinmonth) . '</td>';
         print '<td class="maxwidth300">' . dol_escape_htmltag(dolGetFirstLineOfText($obj->description)) . '</td>';
      print '</tr>';
   }
}
print '</table>';
print '</div>';

// End of page
llxFooter();
$db->close();
