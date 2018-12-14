<?php
/* Copyright (C) 2007-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Dimitri Mouillard    <dmouillard@teclib.com>
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
 *  Displays the log of actions performed in the module.
 *
 *  \file       htdocs/holiday/view_log.php
 *  \ingroup    holiday
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/common.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Protection if external user
if ($user->societe_id > 0) accessforbidden();

// Si l'utilisateur n'a pas le droit de lire cette page
if(!$user->rights->holiday->read_all) accessforbidden();

$year=GETPOST('year');
if (empty($year))
{
	$tmpdate=dol_getdate(dol_now());
	$year=$tmpdate['year'];
}

// Load translation files required by the page
$langs->load('users');


/*
 * View
 */

$cp = new Holiday($db);

$alltypeleaves=$cp->getTypes(1,-1);    // To have labels

llxHeader('', $langs->trans('CPTitreMenu').' ('.$langs->trans("Year").' '.$year.')');

// Recent changes are more important than old changes
$log_holiday = $cp->fetchLog('ORDER BY cpl.rowid DESC', " AND date_action BETWEEN '".$db->idate(dol_get_first_day($year,1,1))."' AND '".$db->idate(dol_get_last_day($year,12,1))."'");	// Load $cp->logs

$pagination='<div class="pagination"><ul><li class="pagination"><a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'"><i class="fa fa-chevron-left" title="Previous"></i></a><li class="pagination"><span class="active">'.$langs->trans("Year").' '.$year.'</span></li><li class="pagination"><a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'"><i class="fa fa-chevron-right" title="Next"></i></a></li></lu></div>';
print load_fiche_titre($langs->trans('LogCP'), $pagination, 'title_hrm.png');

print '<div class="info">'.$langs->trans('LastUpdateCP').': '."\n";
$lastUpdate = $cp->getConfCP('lastUpdate');
if ($lastUpdate)
{
    $monthLastUpdate = $lastUpdate[4].$lastUpdate[5];
    $yearLastUpdate = $lastUpdate[0].$lastUpdate[1].$lastUpdate[2].$lastUpdate[3];
    print '<strong>'.dol_print_date($db->jdate($cp->getConfCP('lastUpdate')),'dayhour','tzuser').'</strong>';
    print '<br>'.$langs->trans("MonthOfLastMonthlyUpdate").': <strong>'.$yearLastUpdate.'-'.$monthLastUpdate.'</strong>'."\n";
}
else print $langs->trans('None');
print "</div><br>\n";

$moreforfilter='';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'" id="tablelines3">'."\n";

print '<tbody>';

print '<tr class="liste_titre">';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '</tr>';

print '<tr class="liste_titre">';
print_liste_field_titre('ID');
print_liste_field_titre('Date', $_SERVER["PHP_SELF"], '', '', '', 'align="center"');
print_liste_field_titre('ActionByCP');
print_liste_field_titre('UserUpdateCP');
print_liste_field_titre('Description');
print_liste_field_titre('Type');
print_liste_field_titre('PrevSoldeCP', $_SERVER["PHP_SELF"], '', '', '', 'align="right"');
print_liste_field_titre('NewSoldeCP', $_SERVER["PHP_SELF"], '', '', '', 'align="right"');
print '</tr>';


foreach($cp->logs as $logs_CP)
{
   	$user_action = new User($db);
   	$user_action->fetch($logs_CP['fk_user_action']);

   	$user_update = new User($db);
   	$user_update->fetch($logs_CP['fk_user_update']);

   	print '<tr class="oddeven">';
   	print '<td>'.$logs_CP['rowid'].'</td>';
   	print '<td style="text-align: center;">'.$logs_CP['date_action'].'</td>';
   	print '<td>'.$user_action->getNomUrl(-1).'</td>';
   	print '<td>'.$user_update->getNomUrl(-1).'</td>';
   	print '<td>'.$logs_CP['type_action'].'</td>';
   	print '<td>';
   	$label = (($alltypeleaves[$logs_CP['fk_type']]['code'] && $langs->trans($alltypeleaves[$logs_CP['fk_type']]['code'])!=$alltypeleaves[$logs_CP['fk_type']]['code']) ? $langs->trans($alltypeleaves[$logs_CP['fk_type']]['code']) : $alltypeleaves[$logs_CP['fk_type']]['label']);
	print $label?$label:$logs_CP['fk_type'];
   	print '</td>';
   	print '<td style="text-align: right;">'.price2num($logs_CP['prev_solde'],5).' '.$langs->trans('days').'</td>';
   	print '<td style="text-align: right;">'.price2num($logs_CP['new_solde'],5).' '.$langs->trans('days').'</td>';
   	print '</tr>'."\n";
}

if ($log_holiday == '2')
{
    print '<tr '.$bc[false].'>';
    print '<td colspan="8" class="opacitymedium">'.$langs->trans('NoRecordFound').'</td>';
    print '</tr>';
}

print '</tbody>'."\n";
print '</table>'."\n";
print '</div>';

// End of page
llxFooter();
$db->close();
