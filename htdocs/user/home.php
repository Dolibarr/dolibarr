<?php
/* Copyright (C) 2005-2018	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2024	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2019		Nicolas ZABOURI		<info@inovea-conseil.com>
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

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'userhome'; // To manage different context of search

if (!$user->hasRight('user', 'user', 'lire') && !$user->admin) {
	// Redirection vers la page de l'utilisateur
	header("Location: card.php?id=".$user->id);
	exit;
}

// Load translation files required by page
$langs->load("users");

$permissiontoreadgroup = true;
if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
	$permissiontoreadgroup = (!empty($user->admin) || $user->hasRight("user", "group_advance", "read"));
}

// Security check (for external users)
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}

$companystatic = new Societe($db);
$fuserstatic = new User($db);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('userhome'));
if (!isset($form) || !is_object($form)) {
	$form = new Form($db);
}
// Load $resultboxes (selectboxlist + boxactivated + boxlista + boxlistb)
$resultboxes = FormOther::getBoxesArea($user, "1");

if (GETPOST('addbox')) {
	// Add box (when submit is done from a form when ajax disabled)
	require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
	$zone = GETPOSTINT('areacode');
	$userid = GETPOSTINT('userid');
	$boxorder = GETPOST('boxorder', 'aZ09');
	$boxorder .= GETPOST('boxcombo', 'aZ09');
	$result = InfoBox::saveboxorder($db, $zone, $boxorder, $userid);
	if ($result > 0) {
		setEventMessages($langs->trans("BoxAdded"), null);
	}
}

$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);


/*
 * View
 */

$title = $langs->trans("MenuUsersAndGroups");
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-home');


print load_fiche_titre($langs->trans("MenuUsersAndGroups"), $resultboxes['selectboxlist'], 'user');


// Search User
$searchbox = '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
$searchbox .= '<input type="hidden" name="token" value="'.newToken().'">';

$searchbox .= '<table class="noborder nohover centpercent">';
$searchbox .= '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Search").'</td></tr>';
$searchbox .= '<tr><td>';
$searchbox .= $langs->trans("User").':</td><td><input class="flat inputsearch width200" type="text" name="search_user"></td></tr>';

// Search Group
if ($permissiontoreadgroup) {
	$searchbox .= '<tr><td>';
	$searchbox .= $langs->trans("Group").':</td><td><input class="flat inputsearch width200" type="text" name="search_group"></td></tr>';
}

$searchbox .= '<tr><td class="center" colspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
$searchbox .= "</table><br>\n";

$searchbox .= '</form>';


/*
 * Latest created users
 */

$lastcreatedbox = '';
$sql = "SELECT DISTINCT u.rowid, u.lastname, u.firstname, u.admin, u.login, u.fk_soc, u.datec, u.statut";
$sql .= ", u.entity";
$sql .= ", u.ldap_sid";
$sql .= ", u.photo";
$sql .= ", u.admin";
$sql .= ", u.email";
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
if (!empty($socid)) {
	$sql .= " AND u.fk_soc = ".((int) $socid);
}
$sql .= $db->order("u.datec", "DESC");
$sql .= $db->plimit($max);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$lastcreatedbox .= '<div class="div-table-responsive-no-min">';
	$lastcreatedbox .= '<table class="noborder centpercent">';
	$lastcreatedbox .= '<tr class="liste_titre"><td colspan="3" class="valignmiddle">';
	$lastcreatedbox .= '<span class="valignmiddle">'.$langs->trans("LastUsersCreated", min($num, $max)).'</span>';
	$lastcreatedbox .= '<a class="valignmiddle marginleftonlyshort" href="'.DOL_URL_ROOT.'/user/list.php?sortfield=u.datec&sortorder=DESC" title="'.$langs->trans("FullList").'">';
	$lastcreatedbox .= '<span class="badge marginleftonlyshort valignmiddle">...</span>';
	$lastcreatedbox .= '</a>';
	$lastcreatedbox .= '</td>';
	$lastcreatedbox .= '<td class="right" colspan="2">';
	//$lastcreatedbox .= '<a class="commonlink" href="'.DOL_URL_ROOT.'/user/list.php?sortfield=u.datec&sortorder=DESC">'.$langs->trans("FullList");
	$lastcreatedbox .= '</td>';
	$lastcreatedbox .= '</tr>'."\n";
	$i = 0;

	while ($i < $num && $i < $max) {
		$obj = $db->fetch_object($resql);

		$fuserstatic->id = $obj->rowid;
		$fuserstatic->statut = $obj->statut;
		$fuserstatic->status = $obj->statut;
		$fuserstatic->lastname = $obj->lastname;
		$fuserstatic->firstname = $obj->firstname;
		$fuserstatic->login = $obj->login;
		$fuserstatic->photo = $obj->photo;
		$fuserstatic->admin = $obj->admin;
		$fuserstatic->email = $obj->email;
		$fuserstatic->socid = $obj->fk_soc;

		$companystatic->id = $obj->fk_soc;
		$companystatic->name = $obj->name;
		$companystatic->code_client = $obj->code_client;
		$companystatic->canvas = $obj->canvas;

		$lastcreatedbox .= '<tr class="oddeven">';
		$lastcreatedbox .= '<td class="nowraponall tdoverflowmax150">';
		$lastcreatedbox .= $fuserstatic->getNomUrl(-1);
		if (isModEnabled('multicompany') && $obj->admin && !$obj->entity) {
			$lastcreatedbox .= img_picto($langs->trans("SuperAdministratorDesc"), 'redstar');
		} elseif ($obj->admin) {
			$lastcreatedbox .= img_picto($langs->trans("AdministratorDesc"), 'star');
		}
		$lastcreatedbox .= "</td>";
		$lastcreatedbox .= '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->login).'">'.dol_escape_htmltag($obj->login).'</td>';
		$texttoshow = '';
		if ($obj->fk_soc) {
			$texttoshow .= $companystatic->getNomUrl(1);
		} else {
			$texttoshow .= '<span class="opacitymedium">'.$langs->trans("InternalUser").'</span>';
		}
		if ($obj->ldap_sid) {
			$texttoshow .= ' <span class="opacitymedium">('.$langs->trans("DomainUser").')</span>';
		}
		$entity = $obj->entity;
		$entitystring = '';
		// TODO Set of entitystring should be done with a hook
		if (isModEnabled('multicompany') && is_object($mc)) {
			if (empty($entity)) {
				$entitystring = $langs->trans("AllEntities");
			} else {
				$mc->getInfo($entity);
				$entitystring = $mc->label;
			}
		}
		$texttoshow .= ($entitystring ? ' <span class="opacitymedium">('.$entitystring.')</span>' : '');
		$lastcreatedbox .= '<td class="tdoverflowmax150" title="'.dol_escape_htmltag(dol_string_nohtmltag($texttoshow)).'">';
		$lastcreatedbox .= $texttoshow;
		$lastcreatedbox .= '</td>';
		$lastcreatedbox .= '<td class="center nowrap">'.dol_print_date($db->jdate($obj->datec), 'dayhour').'</td>';
		$lastcreatedbox .= '<td class="right">';
		$lastcreatedbox .= $fuserstatic->getLibStatut(3);
		$lastcreatedbox .= '</td>';

		$lastcreatedbox .= '</tr>';
		$i++;
	}
	$lastcreatedbox .= "</table>";
	$lastcreatedbox .= "</div><br>";

	$db->free($resql);
} else {
	dol_print_error($db);
}


/*
 * Last groups created
 */
$lastgroupbox = '';
if ($permissiontoreadgroup) {
	$sql = "SELECT g.rowid, g.nom as name, g.note, g.entity, g.datec";
	$sql .= " FROM ".MAIN_DB_PREFIX."usergroup as g";
	if (isModEnabled('multicompany') && $conf->entity == 1 && (getDolGlobalInt('MULTICOMPANY_TRANSVERSE_MODE') || ($user->admin && !$user->entity))) {
		$sql .= " WHERE g.entity IS NOT NULL";
	} else {
		$sql .= " WHERE g.entity IN (0, ".$conf->entity.")";
	}
	$sql .= $db->order("g.datec", "DESC");
	$sql .= $db->plimit($max);

	$resql = $db->query($sql);
	if ($resql) {
		$colspan = 1;
		if (isModEnabled('multicompany')) {
			$colspan++;
		}
		$num = $db->num_rows($resql);

		$lastgroupbox .= '<div class="div-table-responsive-no-min">';
		$lastgroupbox .= '<table class="noborder centpercent">';
		$lastgroupbox .= '<tr class="liste_titre"><td colspan="'.$colspan.'">';
		$lastgroupbox .= '<span class="valignmiddle">'.$langs->trans("LastGroupsCreated", ($num ? $num : $max)).'</span>';
		$lastgroupbox .= '<a class="valignmiddle marginleftonlyshort" href="'.DOL_URL_ROOT.'/user/group/list.php?sortfield=g.datec&sortorder=DESC" title="'.$langs->trans("FullList").'">';
		$lastgroupbox .= '<span class="badge marginleftonlyshort valignmiddle">...</span>';
		$lastgroupbox .= '</a>';

		$lastgroupbox .= '</td>';
		$lastgroupbox .= '<td class="right">';
		//$lastgroupbox .= '<a class="commonlink" href="'.DOL_URL_ROOT.'/user/group/list.php?sortfield=g.datec&sortorder=DESC">'.$langs->trans("FullList");
		$lastgroupbox .= '</td>';
		$lastgroupbox .= '</tr>';
		$i = 0;

		$grouptemp = new UserGroup($db);

		while ($i < $num && (!$max || $i < $max)) {
			$obj = $db->fetch_object($resql);

			$grouptemp->id = $obj->rowid;
			$grouptemp->name = $obj->name;
			$grouptemp->note = $obj->note;

			$lastgroupbox .= '<tr class="oddeven">';
			$lastgroupbox .= '<td>';
			$lastgroupbox .= $grouptemp->getNomUrl(1);
			if (!$obj->entity) {
				$lastgroupbox .= img_picto($langs->trans("GlobalGroup"), 'redstar');
			}
			$lastgroupbox .= "</td>";
			if (isModEnabled('multicompany') && is_object($mc)) {
				$mc->getInfo($obj->entity);
				$lastgroupbox .= '<td>';
				$lastgroupbox .= $mc->label;
				$lastgroupbox .= '</td>';
			}
			$lastgroupbox .= '<td class="nowrap right">'.dol_print_date($db->jdate($obj->datec), 'dayhour').'</td>';
			$lastgroupbox .= "</tr>";
			$i++;
		}
		$lastgroupbox .= "</table>";
		$lastgroupbox .= "</div><br>";

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

// boxes
print '<div class="clearboth"></div>';
print '<div class="fichecenter fichecenterbis">';

$boxlist = '<div class="twocolumns">';

$boxlist .= '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';
$boxlist .= $searchbox;
$boxlist .= $resultboxes['boxlista'];
$boxlist .= '</div>'."\n";

$boxlist .= '<div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';
$boxlist .= $lastcreatedbox;
$boxlist .= $lastgroupbox;
$boxlist .= $resultboxes['boxlistb'];
$boxlist .= '</div>'."\n";

$boxlist .= '</div>';

print $boxlist;

print '</div>';

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardUsersGroups', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
