<?php
/* Copyright (C) 2005-2018	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2018	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2019           Nicolas ZABOURI         <info@inovea-conseil.com>
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
 *	\file       htdocs/user/home.php
 *	\brief      Home page of users and groups management
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'userhome'; // To manage different context of search

if (!$user->rights->user->user->lire && !$user->admin)
{
	// Redirection vers la page de l'utilisateur
	header("Location: card.php?id=".$user->id);
	exit;
}

// Load translation files required by page
$langs->load("users");

$canreadperms = true;
if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
	$canreadperms = ($user->admin || $user->rights->user->group_advance->read);
}

// Security check (for external users)
$socid = 0;
if ($user->socid > 0) $socid = $user->socid;

$companystatic = new Societe($db);
$fuserstatic = new User($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('userhome'));


/*
 * View
 */

llxHeader();


print load_fiche_titre($langs->trans("MenuUsersAndGroups"), '', 'user');


print '<div class="fichecenter"><div class="fichethirdleft">';


// Search User
print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<table class="noborder nohover centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Search").'</td></tr>';
print '<tr><td>';
print $langs->trans("User").':</td><td><input class="flat inputsearch" type="text" name="search_user" size="18"></td></tr>';

// Search Group
if ($canreadperms)
{
	print '<tr><td>';
	print $langs->trans("Group").':</td><td><input class="flat inputsearch" type="text" name="search_group" size="18"></td></tr>';
}

print '<tr><td class="center" colspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print "</table><br>\n";

print '</form>';

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Latest created users
 */
$max = 10;

$sql = "SELECT DISTINCT u.rowid, u.lastname, u.firstname, u.admin, u.login, u.fk_soc, u.datec, u.statut";
$sql .= ", u.entity";
$sql .= ", u.ldap_sid";
$sql .= ", u.photo";
$sql .= ", u.admin";
$sql .= ", u.email";
$sql .= ", u.skype";
$sql .= ", s.nom as name";
$sql .= ", s.code_client";
$sql .= ", s.canvas";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON u.fk_soc = s.rowid";
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printUserListWhere', $parameters); // Note that $action and $object may have been modified by hook
if ($reshook > 0) {
	$sql .= $hookmanager->resPrint;
} else {
	$sql .= " WHERE u.entity IN (".getEntity('user').")";
}
if (!empty($socid)) $sql .= " AND u.fk_soc = ".$socid;
$sql .= $db->order("u.datec", "DESC");
$sql .= $db->plimit($max);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("LastUsersCreated", min($num, $max)).'</td>';
	print '<td class="right" colspan="2"><a class="commonlink" href="'.DOL_URL_ROOT.'/user/list.php?sortfield=u.datec&sortorder=DESC">'.$langs->trans("FullList").'</td>';
	print '</tr>';
	$i = 0;

	while ($i < $num && $i < $max)
	{
		$obj = $db->fetch_object($resql);

		$fuserstatic->id = $obj->rowid;
		$fuserstatic->statut = $obj->statut;
		$fuserstatic->lastname = $obj->lastname;
		$fuserstatic->firstname = $obj->firstname;
		$fuserstatic->login = $obj->login;
		$fuserstatic->photo = $obj->photo;
		$fuserstatic->admin = $obj->admin;
		$fuserstatic->email = $obj->email;
		$fuserstatic->skype = $obj->skype;
		$fuserstatic->socid = $obj->fk_soc;

		$companystatic->id = $obj->fk_soc;
		$companystatic->name = $obj->name;
		$companystatic->code_client = $obj->code_client;
		$companystatic->canvas = $obj->canvas;

		print '<tr class="oddeven">';
		print '<td class="nowraponall">';
        print $fuserstatic->getNomUrl(-1);
		if (!empty($conf->multicompany->enabled) && $obj->admin && !$obj->entity)
		{
			print img_picto($langs->trans("SuperAdministrator"), 'redstar');
		}
		elseif ($obj->admin)
		{
			print img_picto($langs->trans("Administrator"), 'star');
		}
		print "</td>";
		print '<td>'.$obj->login.'</td>';
		print "<td>";
		if ($obj->fk_soc)
		{
            print $companystatic->getNomUrl(1);
		}
		else
		{
			print $langs->trans("InternalUser");
		}
		if ($obj->ldap_sid)
		{
			print ' ('.$langs->trans("DomainUser").')';
		}

		$entity = $obj->entity;
		$entitystring = '';
        // TODO Set of entitystring should be done with a hook
		if (!empty($conf->multicompany->enabled) && is_object($mc))
		{
			if (empty($entity))
			{
				$entitystring = $langs->trans("AllEntities");
			}
			else
			{
				$mc->getInfo($entity);
				$entitystring = $mc->label;
			}
		}
        print ($entitystring ? ' ('.$entitystring.')' : '');

		print '</td>';
		print '<td class="center nowrap">'.dol_print_date($db->jdate($obj->datec), 'dayhour').'</td>';
        print '<td class="right">';
        print $fuserstatic->getLibStatut(3);
        print '</td>';

		print '</tr>';
		$i++;
	}
	print "</table>";
	print "</div><br>";

	$db->free($resql);
}
else
{
	dol_print_error($db);
}


/*
 * Last groups created
 */
if ($canreadperms)
{
	$max = 5;

	$sql = "SELECT g.rowid, g.nom as name, g.note, g.entity, g.datec";
	$sql .= " FROM ".MAIN_DB_PREFIX."usergroup as g";
	if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && ($conf->global->MULTICOMPANY_TRANSVERSE_MODE || ($user->admin && !$user->entity)))
	{
		$sql .= " WHERE g.entity IS NOT NULL";
	}
	else
	{
		$sql .= " WHERE g.entity IN (0,".$conf->entity.")";
	}
	$sql .= $db->order("g.datec", "DESC");
	$sql .= $db->plimit($max);

	$resql = $db->query($sql);
	if ($resql)
	{
		$colspan = 1;
		if (!empty($conf->multicompany->enabled)) $colspan++;
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td colspan="'.$colspan.'">'.$langs->trans("LastGroupsCreated", ($num ? $num : $max)).'</td>';
		print '<td class="right"><a class="commonlink" href="'.DOL_URL_ROOT.'/user/group/list.php?sortfield=g.datec&sortorder=DESC">'.$langs->trans("FullList").'</td>';
		print '</tr>';
		$i = 0;

		$grouptemp = new UserGroup($db);

		while ($i < $num && (!$max || $i < $max))
		{
			$obj = $db->fetch_object($resql);

			$grouptemp->id = $obj->rowid;
			$grouptemp->name = $obj->name;
			$grouptemp->note = $obj->note;

			print '<tr class="oddeven">';
			print '<td>';
			print $grouptemp->getNomUrl(1);
			if (!$obj->entity)
			{
				print img_picto($langs->trans("GlobalGroup"), 'redstar');
			}
			print "</td>";
			if (!empty($conf->multicompany->enabled) && is_object($mc))
			{
	        	$mc->getInfo($obj->entity);
	        	print '<td>';
	        	print $mc->label;
	        	print '</td>';
			}
			print '<td class="nowrap right">'.dol_print_date($db->jdate($obj->datec), 'dayhour').'</td>';
			print "</tr>";
			$i++;
		}
		print "</table>";
		print "</div><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

//print '</td></tr></table>';
print '</div></div></div>';

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardUsersGroups', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
