<?php
/* Copyright (C) 2011		Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2013-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2014	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2015		Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
 *   	\file       htdocs/compta/hrm.php
 *		\ingroup    hrm
 *		\brief      Home page for HRM area.
 */

require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
if ($conf->deplacement->enabled) require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
if ($conf->expensereport->enabled) require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';

$langs->load('users');
$langs->load('holidays');
$langs->load('trips');

$socid=GETPOST("socid");

// Protection if external user
if ($user->societe_id > 0) accessforbidden();



/*
 * Actions
 */

// None



/*
 * View
 */

$holiday = new Holiday($db);
$holidaystatic=new Holiday($db);

$childids = $user->getAllChildIds();
$childids[]=$user->id;

llxHeader(array(),$langs->trans('HRMArea'));

print load_fiche_titre($langs->trans("HRMArea"),'', 'title_hrm.png');

print '<div class="fichecenter"><div class="fichethirdleft">';

/*
 * Search expenses
 */
if (! empty($conf->deplacement->enabled) && $user->rights->deplacement->lire)
{
	$langs->load("trips");
    print '<form method="post" action="'.DOL_URL_ROOT.'/compta/deplacement/list.php">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="noborder nohover" width="100%">';
    print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchATripAndExpense").'</td></tr>';
    print "<tr ".$bc[0].">";
    print "<td><label for=\"search_ref\">".$langs->trans("Ref").'</label>:</td><td><input type="text" name="search_ref" id="search_ref" class="flat" size="18"></td>';
    print '<td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
    //print "<tr ".$bc[0]."><td><label for=\"sall\">".$langs->trans("Other").'</label>:</td><td><input type="text" name="sall" id="sall" class="flat" size="18"></td>';
    print '</tr>';
    print "</table></form><br>";
}

if (! empty($conf->expensereport->enabled) && $user->rights->expensereport->lire)
{
	$langs->load("trips");
    print '<form method="post" action="'.DOL_URL_ROOT.'/expensereport/list.php">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="noborder nohover" width="100%">';
    print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchATripAndExpense").'</td></tr>';
    print "<tr ".$bc[0].">";
    print "<td><label for=\"search_ref\">".$langs->trans("Ref").'</label>:</td><td><input type="text" name="search_ref" id="search_ref" class="flat" size="18"></td>';
    print '<td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
    //print "<tr ".$bc[0]."><td><label for=\"sall\">".$langs->trans("Other").'</label>:</td><td><input type="text" name="sall" id="sall" class="flat" size="18"></td>';
    print '</tr>';
    print "</table></form><br>";
}


if (! empty($conf->holiday->enabled))
{
	$user_id = $user->id;

    print '<table class="noborder nohover" width="100%">';
    print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Holidays").'</td></tr>';
    print "<tr ".$bc[0].">";
    print '<td colspan="3">';

    $out='';
    $typeleaves=$holiday->getTypes(1,1);
    foreach($typeleaves as $key => $val)
    {
    	$nb_type = $holiday->getCPforUser($user->id, $val['rowid']);
    	$nb_holiday += $nb_type;
    	$out .= ' - '.$val['label'].': <strong>'.($nb_type?price2num($nb_type):0).'</strong><br>';
    }
    print $langs->trans('SoldeCPUser', round($nb_holiday,5)).'<br>';
    print $out;

    print '</td>';
    print '</tr>';
    print '</table><br>';
}


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

$max=10;

$langs->load("boxes");

// Last trips
if (! empty($conf->deplacement->enabled) && $user->rights->deplacement->lire)
{
	$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, d.rowid, d.dated as date, d.tms as dm, d.km, d.fk_statut";
	$sql.= " FROM ".MAIN_DB_PREFIX."deplacement as d, ".MAIN_DB_PREFIX."user as u";
	if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE u.rowid = d.fk_user";
	$sql.= " AND d.entity = ".$conf->entity;
	if (empty($user->rights->deplacement->readall) && empty($user->rights->deplacement->lire_tous)) $sql.=' AND d.fk_user IN ('.join(',',$childids).')';
	if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND d.fk_soc = s. rowid AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if (!empty($socid)) $sql.= " AND d.fk_soc = ".$socid;
	$sql.= $db->order("d.tms","DESC");
	$sql.= $db->plimit($max, 0);

	$result = $db->query($sql);
	if ($result)
	{
		$var=false;
		$num = $db->num_rows($result);

		$i = 0;

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="2">'.$langs->trans("BoxTitleLastModifiedExpenses",min($max,$num)).'</td>';
		print '<td align="right">'.$langs->trans("FeesKilometersOrAmout").'</td>';
		print '<td align="right">'.$langs->trans("DateModificationShort").'</td>';
		print '<td width="16">&nbsp;</td>';
		print '</tr>';
		if ($num)
		{
			$total_ttc = $totalam = $total = 0;

			$deplacementstatic=new Deplacement($db);
			$userstatic=new User($db);
			while ($i < $num && $i < $max)
			{
				$obj = $db->fetch_object($result);
				$deplacementstatic->ref=$obj->rowid;
				$deplacementstatic->id=$obj->rowid;
				$userstatic->id=$obj->uid;
				$userstatic->lastname=$obj->lastname;
				$userstatic->firstname=$obj->firstname;
				print '<tr '.$bc[$var].'>';
				print '<td>'.$deplacementstatic->getNomUrl(1).'</td>';
				print '<td>'.$userstatic->getNomUrl(1).'</td>';
				print '<td align="right">'.$obj->km.'</td>';
				print '<td align="right">'.dol_print_date($db->jdate($obj->dm),'day').'</td>';
				print '<td>'.$deplacementstatic->LibStatut($obj->fk_statut,3).'</td>';
				print '</tr>';
				$var=!$var;
				$i++;
			}

		}
		else
		{
			print '<tr '.$bc[$var].'><td colspan="5">'.$langs->trans("None").'</td></tr>';
		}
		print '</table><br>';
	}
	else dol_print_error($db);
}

if (! empty($conf->expensereport->enabled) && $user->rights->expensereport->lire)
{
	$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, x.rowid, x.ref, x.date_debut as date, x.tms as dm, x.total_ttc, x.fk_statut as status";
	$sql.= " FROM ".MAIN_DB_PREFIX."expensereport as x, ".MAIN_DB_PREFIX."user as u";
	if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE u.rowid = x.fk_user_author";
	$sql.= " AND x.entity = ".$conf->entity;
	if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous)) $sql.=' AND x.fk_user_author IN ('.join(',',$childids).')';
	//if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND x.fk_soc = s. rowid AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	//if (!empty($socid)) $sql.= " AND x.fk_soc = ".$socid;
	$sql.= $db->order("x.tms","DESC");
	$sql.= $db->plimit($max, 0);

	$result = $db->query($sql);
	if ($result)
	{
		$var=false;
		$num = $db->num_rows($result);

		$i = 0;

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="2">'.$langs->trans("BoxTitleLastModifiedExpenses",min($max,$num)).'</td>';
		print '<td align="right">'.$langs->trans("TotalTTC").'</td>';
		print '<td align="right">'.$langs->trans("DateModificationShort").'</td>';
		print '<td width="16">&nbsp;</td>';
		print '</tr>';
		if ($num)
		{
			$total_ttc = $totalam = $total = 0;

			$expensereportstatic=new ExpenseReport($db);
			$userstatic=new User($db);
			while ($i < $num && $i < $max)
			{
				$obj = $db->fetch_object($result);
				$expensereportstatic->id=$obj->rowid;
				$expensereportstatic->ref=$obj->ref;
				$userstatic->id=$obj->uid;
				$userstatic->lastname=$obj->lastname;
				$userstatic->firstname=$obj->firstname;
				print '<tr '.$bc[$var].'>';
				print '<td>'.$expensereportstatic->getNomUrl(1).'</td>';
				print '<td>'.$userstatic->getNomUrl(1).'</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '<td align="right">'.dol_print_date($db->jdate($obj->dm),'day').'</td>';
				print '<td>'.$expensereportstatic->LibStatut($obj->status,3).'</td>';
				print '</tr>';
				$var=!$var;
				$i++;
			}

		}
		else
		{
			print '<tr '.$bc[$var].'><td colspan="5">'.$langs->trans("None").'</td></tr>';
		}
		print '</table><br>';
	}
	else dol_print_error($db);
}


print '</div></div></div>';



llxFooter();

$db->close();
