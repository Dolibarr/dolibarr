<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *   	\file       htdocs/admin/perms.php
 *      \ingroup    core
 *		\brief      Page to setup default permissions of a new user
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('admin', 'users', 'other'));

$action = GETPOST('action', 'aZ09');

$entity = $conf->entity;

if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == 'add') {
	$sql = "UPDATE ".MAIN_DB_PREFIX."rights_def SET bydefault=1";
	$sql .= " WHERE id = ".GETPOSTINT("pid");
	$sql .= " AND entity = ".$conf->entity;
	$db->query($sql);
}

if ($action == 'remove') {
	$sql = "UPDATE ".MAIN_DB_PREFIX."rights_def SET bydefault=0";
	$sql .= " WHERE id = ".GETPOSTINT('pid');
	$sql .= " AND entity = ".$conf->entity;
	$db->query($sql);
}


/*
 * View
 */

$form = new Form($db);

$wikihelp = 'EN:Setup_Security|FR:Paramétrage_Sécurité|ES:Configuración_Seguridad';

llxHeader('', $langs->trans("DefaultRights"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-perms');

print load_fiche_titre($langs->trans("SecuritySetup"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("DefaultRightsDesc")." ".$langs->trans("OnlyActiveElementsAreShown")."</span><br><br>\n";

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

					'@phan-var-force DolibarrModules $objMod';

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
'@phan-var-force DolibarrModules[] $modules';

$head = security_prepare_head();

print dol_get_fiche_head($head, 'default', '', -1);


// Show warning about external users
print info_admin(showModulesExludedForExternal($modules)).'<br>'."\n";

print "\n";
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Module").'</td>';
print '<td class="center">'.$langs->trans("Default").'</td>';
print '<td class="center" width="24">&nbsp;</td>';
print '<td>'.$langs->trans("Permissions").'</td>';
if ($user->admin) {
	print '<td class="right"></td>';
}
print '</tr>'."\n";

//print "xx".$conf->global->MAIN_USE_ADVANCED_PERMS;
$sql = "SELECT r.id, r.libelle as label, r.module, r.perms, r.subperms, r.module_position, r.bydefault";
$sql .= " FROM ".MAIN_DB_PREFIX."rights_def as r";
$sql .= " WHERE r.libelle NOT LIKE 'tou%'"; // On ignore droits "tous"
$sql .= " AND r.entity = ".((int) $entity);
if (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
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

		// Check if permission we found is inside a module definition. If not, we discard it.
		$found = false;
		foreach ($modules[$obj->module]->rights as $key => $val) {
			if ($val[4] == $obj->perms && (empty($val[5]) || $val[5] == $obj->subperms)) {
				$found = true;
				break;
			}
		}
		if (!$found) {
			$i++;
			continue;
		}

		// Break found, it's a new module to catch
		if (isset($obj->module) && ($oldmod != $obj->module)) {
			$oldmod = $obj->module;

			// Break detected, we get objMod
			$objMod = $modules[$obj->module];
			$picto = ($objMod->picto ? $objMod->picto : 'generic');

			// Show break line
			print '<tr class="oddeven trforbreak">';
			print '<td class="maxwidthonsmartphone tdoverflowmax200" title="'.dol_escape_htmltag($objMod->getName()).'">';
			print img_object('', $picto, 'class="pictoobjectwidth paddingright"').' '.$objMod->getName();
			print '<a name="'.$objMod->getName().'"></a>';
			print '</td>';
			print '<td>&nbsp;</td>';
			print '<td>&nbsp;</td>';
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
		print '<td class="maxwidthonsmartphone tdoverflowmax200">';
		//print img_object('', $picto, 'class="pictoobjectwidth"').' '.$objMod->getName();
		print '</td>';

		// Tick
		if ($obj->bydefault == 1) {
			print '<td class="center">';
			print '<a class="reposition" href="perms.php?pid='.$obj->id.'&action=remove&token='.newToken().'">';
			//print img_edit_remove();
			print img_picto('', 'switch_on');
			print '</a>';
			print '</td>';
			print '<td class="center">';
			//print img_picto($langs->trans("Active"), 'tick');
			print '</td>';
		} else {
			print '<td class="center">';
			print '<a class="reposition" href="perms.php?pid='.$obj->id.'&action=add&token='.newToken().'">';
			//print img_edit_add();
			print img_picto('', 'switch_off');
			print '</a>';
			print '</td>';
			print '<td class="center">';
			print '&nbsp;';
			print '</td>';
		}

		// Permission and tick
		$permlabel = (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && ($langs->trans("PermissionAdvanced".$obj->id) != "PermissionAdvanced".$obj->id) ? $langs->trans("PermissionAdvanced".$obj->id) : (($langs->trans("Permission".$obj->id) != "Permission".$obj->id) ? $langs->trans("Permission".$obj->id) : $langs->trans($obj->label)));
		print '<td>';
		print $permlabel;
		if ($langs->trans("Permission".$obj->id.'b') != "Permission".$obj->id.'b') {
			print '<br><span class="opacitymedium">'.$langs->trans("Permission".$obj->id.'b').'</span>';
		}
		if ($langs->trans("Permission".$obj->id.'c') != "Permission".$obj->id.'c') {
			print '<br><span class="opacitymedium">'.$langs->trans("Permission".$obj->id.'c').'</span>';
		}
		if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
			if (preg_match('/_advance$/', $obj->perms)) {
				print ' <span class="opacitymedium">('.$langs->trans("AdvancedModeOnly").')</span>';
			}
		}
		print '</td>';

		// Permission id
		if ($user->admin) {
			print '<td class="right">';
			$htmltext = $langs->trans("ID").': '.$obj->id;
			$htmltext .= '<br>'.$langs->trans("Permission").': user->hasRight(\''.dol_escape_htmltag($obj->module).'\', \''.dol_escape_htmltag($obj->perms).'\''.($obj->subperms ? ', \''.dol_escape_htmltag($obj->subperms).'\'' : '').')';
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
