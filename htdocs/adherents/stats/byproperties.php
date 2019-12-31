<?php
/* Copyright (c) 2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *	    \file       htdocs/adherents/stats/byproperties.php
 *      \ingroup    member
 *		\brief      Page with statistics on members
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

$graphwidth = 700;
$mapratio = 0.5;
$graphheight = round($graphwidth * $mapratio);

$mode=GETPOST('mode')?GETPOST('mode'):'';


// Security check
if ($user->socid > 0)
{
    $action = '';
    $socid = $user->socid;
}
$result=restrictedArea($user, 'adherent', '', '', 'cotisation');

$year = strftime("%Y", time());
$startyear=$year-2;
$endyear=$year;

// Load translation files required by the page
$langs->loadLangs(array("companies","members"));


/*
 * View
 */

$memberstatic=new Adherent($db);

llxHeader('', $langs->trans("MembersStatisticsByProperties"), '', '', 0, 0, array('https://www.google.com/jsapi'));

$title=$langs->trans("MembersStatisticsByProperties");

print load_fiche_titre($title, '');

dol_mkdir($dir);

$tab='byproperties';

$data = array();
$sql.="SELECT COUNT(d.rowid) as nb, MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate, d.morphy as code";
$sql.=" FROM ".MAIN_DB_PREFIX."adherent as d";
$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."subscription as s ON s.fk_adherent = d.rowid";
$sql.=" WHERE d.entity IN (".getEntity('adherent').")";
$sql.=" AND d.statut = 1";
$sql.=" GROUP BY d.morphy";

$foundphy=$foundmor=0;

// Define $data array
dol_syslog("Count member", LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
	$num=$db->num_rows($resql);
	$i=0;
	while ($i < $num)
	{
		$obj=$db->fetch_object($resql);

		if ($obj->code == 'phy') $foundphy++;
		if ($obj->code == 'mor') $foundmor++;

		$data[]=array('label'=>$obj->code, 'nb'=>$obj->nb, 'lastdate'=>$db->jdate($obj->lastdate), 'lastsubscriptiondate'=>$db->jdate($obj->lastsubscriptiondate));

		$i++;
	}
	$db->free($resql);
}
else
{
	dol_print_error($db);
}


$head = member_stats_prepare_head($adh);

dol_fiche_head($head, 'statsbyproperties', $langs->trans("Statistics"), -1, 'user');


// Print title
if (! count($data))
{
	print $langs->trans("NoValidatedMemberYet").'<br>';
	print '<br>';
}
else
{
	print load_fiche_titre($langs->trans("MembersByNature"), '', '');
}

// Print array
print '<table class="liste centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Nature").'</td>';
print '<td class="right">'.$langs->trans("NbOfMembers").'</td>';
print '<td class="center">'.$langs->trans("LastMemberDate").'</td>';
print '<td class="center">'.$langs->trans("LatestSubscriptionDate").'</td>';
print '</tr>';

if (! $foundphy) $data[]=array('label'=>'phy','nb'=>'0','lastdate'=>'', 'lastsubscriptiondate'=>'');
if (! $foundmor) $data[]=array('label'=>'mor','nb'=>'0','lastdate'=>'', 'lastsubscriptiondate'=>'');

foreach ($data as $val)
{
	print '<tr class="oddeven">';
	print '<td>'.$memberstatic->getmorphylib($val['label']).'</td>';
	print '<td class="right">'.$val['nb'].'</td>';
	print '<td class="center">'.dol_print_date($val['lastdate'], 'dayhour').'</td>';
	print '<td class="center">'.dol_print_date($val['lastsubscriptiondate'], 'dayhour').'</td>';
	print '</tr>';
}

print '</table>';


dol_fiche_end();

// End of page
llxFooter();
$db->close();
