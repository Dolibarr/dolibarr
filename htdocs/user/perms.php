<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2020		Tobias Sekan			<tobias.sekan@startmail.com>
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
 *		\file		htdocs/user/perms.php
 *		\brief		Page to set permission of a user record
 */

if (!defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1'); // Force use of CSRF protection with tokens even for GET
}

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by page
$langs->loadLangs(array('users', 'admin'));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$module = GETPOST('module', 'alpha');
$rights = GETPOST('rights', 'int');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'userperms'; // To manage different context of search

if (!isset($id) || empty($id)) {
	accessforbidden();
}

// Define if user can read permissions
$canreaduser = ($user->admin || $user->rights->user->user->lire);
// Define if user can modify other users and permissions
$caneditperms = ($user->admin || $user->rights->user->user->creer);
// Advanced permissions
if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS)) {
	$canreaduser = ($user->admin || ($user->rights->user->user->lire && $user->rights->user->user_advance->readperms));
	$caneditselfperms = ($user->id == $id && $user->rights->user->self_advance->writeperms);
	$caneditperms = (($caneditperms || $caneditselfperms) ? 1 : 0);
}

// Security check
$socid = 0;
if (isset($user->socid) && $user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->rights->user->self->creer) ? '' : 'user');
// A user can always read its own card if not advanced perms enabled, or if he has advanced perms, except for admin
if ($user->id == $id && (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->user->self_advance->readperms) && empty($user->admin))) {
	accessforbidden();
}

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);
if ($user->id <> $id && !$canreaduser) {
	accessforbidden();
}

$object = new User($db);
$object->fetch($id, '', '', 1);
$object->getrights();

$entity = $conf->entity;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('usercard', 'userperms', 'globalcard'));


/*
 * Actions
 */

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'addrights' && $caneditperms && $confirm == 'yes') {
		$edituser = new User($db);
		$edituser->fetch($object->id);
		$result = $edituser->addrights($rights, $module, '', $entity);
		if ($result < 0) {
			setEventMessages($edituser->error, $edituser->errors, 'errors');
		}

		// If we are changing our own permissions, we reload permissions and menu
		if ($object->id == $user->id) {
			$user->clearrights();
			$user->getrights();
			$menumanager->loadMenu();
		}

		$object->clearrights();
		$object->getrights();
	}

	if ($action == 'delrights' && $caneditperms && $confirm == 'yes') {
		$edituser = new User($db);
		$edituser->fetch($object->id);
		$result = $edituser->delrights($rights, $module, '', $entity);
		if ($result < 0) {
			setEventMessages($edituser->error, $edituser->errors, 'errors');
		}

		// If we are changing our own permissions, we reload permissions and menu
		if ($object->id == $user->id) {
			$user->clearrights();
			$user->getrights();
			$menumanager->loadMenu();
		}

		$object->clearrights();
		$object->getrights();
	}
}


/*
 *	View
 */

$form = new Form($db);

llxHeader('', $langs->trans("Permissions"));

$head = user_prepare_head($object);

$title = $langs->trans("User");
print dol_get_fiche_head($head, 'rights', $title, -1, 'user');


$db->begin();

// Search all modules with permission and reload permissions def.
$modules = array();
$modulesdir = dolGetModulesDirs();

foreach ($modulesdir as $dir) {
	$handle = @opendir(dol_osencode($dir));
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false) {
			if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php') {
				$modName = substr($file, 0, dol_strlen($file) - 10);

				if ($modName) {
					include_once $dir.$file;
					$objMod = new $modName($db);

					// Load all lang files of module
					if (isset($objMod->langfiles) && is_array($objMod->langfiles)) {
						foreach ($objMod->langfiles as $domain) {
							$langs->load($domain);
						}
					}
					// Load all permissions
					if ($objMod->rights_class) {
						$ret = $objMod->insert_permissions(0, $entity);
						$modules[$objMod->rights_class] = $objMod;
						//print "modules[".$objMod->rights_class."]=$objMod;";
					}
				}
			}
		}
	}
}

$db->commit();

// Read permissions of user
$permsuser = array();

$sql = "SELECT DISTINCT ur.fk_id";
$sql .= " FROM ".MAIN_DB_PREFIX."user_rights as ur";
$sql .= " WHERE ur.entity = ".((int) $entity);
$sql .= " AND ur.fk_user = ".((int) $object->id);

dol_syslog("get user perms", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);
		array_push($permsuser, $obj->fk_id);
		$i++;
	}
	$db->free($result);
} else {
	dol_print_error($db);
}

// Lecture des droits groupes
$permsgroupbyentity = array();

$sql = "SELECT DISTINCT gr.fk_id, gu.entity";
$sql .= " FROM ".MAIN_DB_PREFIX."usergroup_rights as gr,";
$sql .= " ".MAIN_DB_PREFIX."usergroup_user as gu";
$sql .= " WHERE gr.entity = ".((int) $entity);
$sql .= " AND gr.fk_usergroup = gu.fk_usergroup";
$sql .= " AND gu.fk_user = ".((int) $object->id);

dol_syslog("get user perms", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);
		if (!isset($permsgroupbyentity[$obj->entity])) {
			$permsgroupbyentity[$obj->entity] = array();
		}
		array_push($permsgroupbyentity[$obj->entity], $obj->fk_id);
		$i++;
	}
	$db->free($result);
} else {
	dol_print_error($db);
}


/*
 * Part to add/remove permissions
 */

$linkback = '';

if ($user->rights->user->user->lire || $user->admin) {
	$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
}

$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'" class="refid">';
$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
$morehtmlref .= '</a>';

dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin, 'rowid', 'ref', $morehtmlref);


print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

// Login
print '<tr><td class="titlefield">'.$langs->trans("Login").'</td>';
if (!empty($object->ldap_sid) && $object->statut == 0) {
	print '<td class="error">';
	print $langs->trans("LoginAccountDisableInDolibarr");
	print '</td>';
} else {
	print '<td>';
	$addadmin = '';
	if (property_exists($object, 'admin')) {
		if (!empty($conf->multicompany->enabled) && !empty($object->admin) && empty($object->entity)) {
			$addadmin .= img_picto($langs->trans("SuperAdministratorDesc"), "redstar", 'class="paddingleft"');
		} elseif (!empty($object->admin)) {
			$addadmin .= img_picto($langs->trans("AdministratorDesc"), "star", 'class="paddingleft"');
		}
	}
	print showValueWithClipboardCPButton($object->login).$addadmin;
	print '</td>';
}
print '</tr>'."\n";

print '</table>';

print '</div>';
print '<br>';

if ($user->admin) {
	print info_admin($langs->trans("WarningOnlyPermissionOfActivatedModules"));
}
// If edited user is an extern user, we show warning for external users
if (! empty($object->socid)) {
	print info_admin(showModulesExludedForExternal($modules))."\n";
}

$parameters = array('permsgroupbyentity'=>$permsgroupbyentity);
$reshook = $hookmanager->executeHooks('insertExtraHeader', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}


print "\n";
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Module").'</td>';
if (($caneditperms && empty($objMod->rights_admin_allowed)) || empty($object->admin)) {
	if ($caneditperms) {
		print '<td class="center nowrap">';
		print '<a class="reposition commonlink" title="'.dol_escape_htmltag($langs->trans("All")).'" alt="'.dol_escape_htmltag($langs->trans("All")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addrights&token='.newToken().'&entity='.$entity.'&module=allmodules&confirm=yes">'.$langs->trans("All")."</a>";
		print ' / ';
		print '<a class="reposition commonlink" title="'.dol_escape_htmltag($langs->trans("None")).'" alt="'.dol_escape_htmltag($langs->trans("None")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delrights&token='.newToken().'&entity='.$entity.'&module=allmodules&confirm=yes">'.$langs->trans("None")."</a>";
		print '</td>';
	}
	print '<td class="center" width="24">&nbsp;</td>';
}
print '<td>'.$langs->trans("Permissions").'</td>';
if ($user->admin) {
	print '<td class="right"></td>';
}
print '</tr>'."\n";


// Fix bad value for module_position in table
// ------------------------------------------
$sql = "SELECT r.id, r.libelle as label, r.module, r.perms, r.subperms, r.module_position, r.bydefault";
$sql .= " FROM ".MAIN_DB_PREFIX."rights_def as r";
$sql .= " WHERE r.libelle NOT LIKE 'tou%'"; // On ignore droits "tous"
$sql .= " AND r.entity = ".((int) $entity);
$sql .= " ORDER BY r.family_position, r.module_position, r.module, r.id";

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;
	$oldmod = '';

	while ($i < $num) {
		$obj = $db->fetch_object($result);

		// If line is for a module that does not exist anymore (absent of includes/module), we ignore it
		if (!isset($obj->module) || empty($modules[$obj->module])) {
			$i++;
			continue;
		}

		// Special cases
		if (!empty($conf->reception->enabled)) {
			// The 2 permissions in fournisseur modules are replaced by permissions into reception module
			if ($obj->module == 'fournisseur' && $obj->perms == 'commande' && $obj->subperms == 'receptionner') {
				$i++;
				continue;
			}
			if ($obj->module == 'fournisseur' && $obj->perms == 'commande_advance' && $obj->subperms == 'check') {
				$i++;
				continue;
			}
		}

		$objMod = $modules[$obj->module];

		// Save field module_position in database if value is wrong
		if (empty($obj->module_position) || (is_object($objMod) && $objMod->isCoreOrExternalModule() == 'external' && $obj->module_position < 100000)) {
			if (is_object($modules[$obj->module]) && ($modules[$obj->module]->module_position > 0)) {
				// TODO Define familyposition
				//$familyposition = $modules[$obj->module]->family_position;
				$familyposition = 0;

				$newmoduleposition = $modules[$obj->module]->module_position;

				// Correct $newmoduleposition position for external modules
				$objMod = $modules[$obj->module];
				if (is_object($objMod) && $objMod->isCoreOrExternalModule() == 'external' && $newmoduleposition < 100000) {
					$newmoduleposition += 100000;
				}

				$sqlupdate = 'UPDATE '.MAIN_DB_PREFIX."rights_def SET module_position = ".((int) $newmoduleposition).",";
				$sqlupdate .= " family_position = ".((int) $familyposition);
				$sqlupdate .= " WHERE module_position = ".((int) $obj->module_position)." AND module = '".$db->escape($obj->module)."'";

				$db->query($sqlupdate);
			}
		}
	}
}



//print "xx".$conf->global->MAIN_USE_ADVANCED_PERMS;
$sql = "SELECT r.id, r.libelle as label, r.module, r.perms, r.subperms, r.module_position, r.bydefault";
$sql .= " FROM ".MAIN_DB_PREFIX."rights_def as r";
$sql .= " WHERE r.libelle NOT LIKE 'tou%'"; // On ignore droits "tous"
$sql .= " AND r.entity = ".((int) $entity);
if (empty($conf->global->MAIN_USE_ADVANCED_PERMS)) {
	$sql .= " AND r.perms NOT LIKE '%_advance'"; // Hide advanced perms if option is not enabled
}
$sql .= " ORDER BY r.family_position, r.module_position, r.module, r.id";

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;
	$oldmod = '';

	while ($i < $num) {
		$obj = $db->fetch_object($result);

		// If line is for a module that does not exist anymore (absent of includes/module), we ignore it
		if (empty($modules[$obj->module])) {
			$i++;
			continue;
		}

		// Special cases
		if (!empty($conf->reception->enabled)) {
			// The 2 permission in fournisseur modules has been replaced by permissions into reception module
			if ($obj->module == 'fournisseur' && $obj->perms == 'commande' && $obj->subperms == 'receptionner') {
				$i++;
				continue;
			}
			if ($obj->module == 'fournisseur' && $obj->perms == 'commande_advance' && $obj->subperms == 'check') {
				$i++;
				continue;
			}
		}

		$objMod = $modules[$obj->module];

		// Save field module_position in database if value is wrong
		/*
		if (empty($obj->module_position) || (is_object($objMod) && $objMod->isCoreOrExternalModule() == 'external' && $obj->module_position < 100000)) {
			if (is_object($modules[$obj->module]) && ($modules[$obj->module]->module_position > 0)) {
				// TODO Define familyposition
				//$familyposition = $modules[$obj->module]->family_position;
				$familyposition = 0;

				$newmoduleposition = $modules[$obj->module]->module_position;

				// Correct $newmoduleposition position for external modules
				$objMod = $modules[$obj->module];
				if (is_object($objMod) && $objMod->isCoreOrExternalModule() == 'external' && $newmoduleposition < 100000) {
					$newmoduleposition += 100000;
				}

				$sqlupdate = 'UPDATE '.MAIN_DB_PREFIX."rights_def SET module_position = ".((int) $newmoduleposition).",";
				$sqlupdate .= " family_position = ".((int) $familyposition);
				$sqlupdate .= " WHERE module_position = ".((int) $obj->module_position)." AND module = '".$db->escape($obj->module)."'";

				$db->query($sqlupdate);
			}
		}
		*/

		// Break found, it's a new module to catch
		if (isset($obj->module) && ($oldmod <> $obj->module)) {
			$oldmod = $obj->module;

			// Break detected, we get objMod
			$objMod = $modules[$obj->module];
			$picto = ($objMod->picto ? $objMod->picto : 'generic');

			// Show break line
			print '<tr class="oddeven trforbreak">';
			print '<td class="maxwidthonsmartphone tdoverflowonsmartphone">';
			print img_object('', $picto, 'class="pictoobjectwidth paddingright"').' '.$objMod->getName();
			print '<a name="'.$objMod->getName().'"></a>';
			print '</td>';
			if (($caneditperms && empty($objMod->rights_admin_allowed)) || empty($object->admin)) {
				if ($caneditperms) {
					print '<td class="center nowrap">';
					print '<a class="reposition" title="'.dol_escape_htmltag($langs->trans("All")).'" alt="'.dol_escape_htmltag($langs->trans("All")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addrights&token='.newToken().'&entity='.$entity.'&module='.$obj->module.'&confirm=yes">'.$langs->trans("All")."</a>";
					print ' / ';
					print '<a class="reposition" title="'.dol_escape_htmltag($langs->trans("None")).'" alt="'.dol_escape_htmltag($langs->trans("None")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delrights&token='.newToken().'&entity='.$entity.'&module='.$obj->module.'&confirm=yes">'.$langs->trans("None")."</a>";
					print '</td>';
				}
				print '<td>&nbsp;</td>';
			} else {
				if ($caneditperms) {
					print '<td>&nbsp;</td>';
				}
				print '<td>&nbsp;</td>';
			}
			print '<td>&nbsp;</td>';

			// Permission id
			if ($user->admin) {
				print '<td class="right"></td>';
			}

			print '</tr>'."\n";
		}

		print '<!-- '.$obj->module.'->'.$obj->perms.($obj->subperms ? '->'.$obj->subperms : '').' -->'."\n";
		print '<tr class="oddeven">';

		// Picto and label of module
		print '<td class="maxwidthonsmartphone tdoverflowonsmartphone">';
		//print img_object('', $picto, 'class="inline-block pictoobjectwidth"').' '.$objMod->getName();
		print '</td>';

		// Permission and tick
		if (!empty($object->admin) && !empty($objMod->rights_admin_allowed)) {    // Permission granted because admin
			if ($caneditperms) {
				print '<td class="center">'.img_picto($langs->trans("Administrator"), 'star').'</td>';
			}
			print '<td class="center nowrap">';
			print img_picto($langs->trans("Active"), 'tick');
			print '</td>';
		} elseif (in_array($obj->id, $permsuser)) {					// Permission granted by user
			if ($caneditperms) {
				print '<td class="center"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delrights&token='.newToken().'&entity='.$entity.'&rights='.$obj->id.'&confirm=yes">';
				//print img_edit_remove($langs->trans("Remove"));
				print img_picto($langs->trans("Remove"), 'switch_on');
				print '</a></td>';
			}
			print '<td class="center nowrap">';
			print img_picto($langs->trans("Active"), 'tick');
			print '</td>';
		} elseif (isset($permsgroupbyentity[$entity]) && is_array($permsgroupbyentity[$entity])) {
			if (in_array($obj->id, $permsgroupbyentity[$entity])) {	// Permission granted by group
				if ($caneditperms) {
					print '<td class="center">';
					print $form->textwithtooltip($langs->trans("Inherited"), $langs->trans("PermissionInheritedFromAGroup"));
					print '</td>';
				}
				print '<td class="center nowrap">';
				print img_picto($langs->trans("Active"), 'tick');
				print '</td>';
			} else {
				// Do not own permission
				if ($caneditperms) {
					print '<td class="center"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addrights&entity='.$entity.'&rights='.$obj->id.'&confirm=yes&token='.newToken().'">';
					//print img_edit_add($langs->trans("Add"));
					print img_picto($langs->trans("Add"), 'switch_off');
					print '</a></td>';
				}
				print '<td>&nbsp;</td>';
			}
		} else {
			// Do not own permission
			if ($caneditperms) {
				print '<td class="center"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addrights&entity='.$entity.'&rights='.$obj->id.'&confirm=yes&token='.newToken().'">';
				//print img_edit_add($langs->trans("Add"));
				print img_picto($langs->trans("Add"), 'switch_off');
				print '</a></td>';
			}
			print '<td>&nbsp;</td>';
		}

		// Description of permission
		$permlabel = (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ($langs->trans("PermissionAdvanced".$obj->id) != ("PermissionAdvanced".$obj->id)) ? $langs->trans("PermissionAdvanced".$obj->id) : (($langs->trans("Permission".$obj->id) != ("Permission".$obj->id)) ? $langs->trans("Permission".$obj->id) : $langs->trans($obj->label)));
		print '<td>';
		print $permlabel;
		if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS)) {
			if (preg_match('/_advance$/', $obj->perms)) {
				print ' <span class="opacitymedium">('.$langs->trans("AdvancedModeOnly").')</span>';
			}
		}
		print '</td>';

		// Permission id
		if ($user->admin) {
			print '<td class="right">';
			$htmltext = $langs->trans("ID").': '.$obj->id;
			$htmltext .= '<br>'.$langs->trans("Permission").': user->rights->'.$obj->module.'->'.$obj->perms.($obj->subperms ? '->'.$obj->subperms : '');
			print $form->textwithpicto('', $htmltext);
			//print '<span class="opacitymedium">'.$obj->id.'</span>';
			print '</td>';
		}

		print '</tr>'."\n";

		$i++;
	}
} else {
	dol_print_error($db);
}
print '</table>';
print '</div>';

$parameters = array();
$reshook = $hookmanager->executeHooks('insertExtraFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
