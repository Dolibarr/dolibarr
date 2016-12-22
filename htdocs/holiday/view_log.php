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

require('../main.inc.php');
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

$langs->load('users');


/*
 * View
 */

$cp = new Holiday($db);

llxHeader(array(),$langs->trans('CPTitreMenu').' ('.$langs->trans("Year").' '.$year.')');

// Recent changes are more important than old changes
$log_holiday = $cp->fetchLog('ORDER BY cpl.rowid DESC', " AND date_action BETWEEN '".$db->idate(dol_get_first_day($year,1,1))."' AND '".$db->idate(dol_get_last_day($year,12,1))."'");	// Load $cp->logs

print load_fiche_titre($langs->trans('LogCP'), '<div class="pagination"><ul><li class="pagination"><a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'">&lt;</a><li class="pagination"><a href="">'.$langs->trans("Year").' '.$year.'</a></li><li class="pagination"><a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'">&gt;</a></li></lu></div>', 'title_hrm.png');

print '<div class="info">'.$langs->trans('LastUpdateCP').': '."\n";
if ($cp->getConfCP('lastUpdate')) print '<strong>'.dol_print_date($db->jdate($cp->getConfCP('lastUpdate')),'dayhour','tzuser').'</strong>';
else print $langs->trans('None');
print "</div><br>\n";

print '<table class="noborder" width="100%">';
print '<tbody>';
print '<tr class="liste_titre">';

print '<td class="liste_titre">'.$langs->trans('ID').'</td>';
print '<td class="liste_titre" align="center">'.$langs->trans('Date').'</td>';
print '<td class="liste_titre">'.$langs->trans('ActionByCP').'</td>';
print '<td class="liste_titre">'.$langs->trans('UserUpdateCP').'</td>';
print '<td class="liste_titre">'.$langs->trans('Description').'</td>';
print '<td class="liste_titre">'.$langs->trans('Type').'</td>';
print '<td class="liste_titre" align="right">'.$langs->trans('PrevSoldeCP').'</td>';
print '<td class="liste_titre" align="right">'.$langs->trans('NewSoldeCP').'</td>';

print '</tr>';
$var=true;

foreach($cp->logs as $logs_CP)
{
   	$var=!$var;

   	$user_action = new User($db);
   	$user_action->fetch($logs_CP['fk_user_action']);

   	$user_update = new User($db);
   	$user_update->fetch($logs_CP['fk_user_update']);

   	print '<tr '.$bc[$var].'>';
   	print '<td>'.$logs_CP['rowid'].'</td>';
   	print '<td style="text-align: center;">'.$logs_CP['date_action'].'</td>';
   	print '<td>'.$user_action->getNomUrl(1).'</td>';
   	print '<td>'.$user_update->getNomUrl(1).'</td>';
   	print '<td>'.$logs_CP['type_action'].'</td>';
   	print '<td>'.$logs_CP['fk_type'].'</td>';
   	print '<td style="text-align: right;">'.price2num($logs_CP['prev_solde'],5).' '.$langs->trans('days').'</td>';
   	print '<td style="text-align: right;">'.price2num($logs_CP['new_solde'],5).' '.$langs->trans('days').'</td>';
   	print '</tr>'."\n";

}

if ($log_holiday == '2')
{
    print '<tr>';
    print '<td colspan="8" '.$bc[false].'>'.$langs->trans('None').'</td>';
    print '</tr>';
}

print '</tbody>'."\n";
print '</table>'."\n";


llxFooter();

$db->close();
