<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2020		Tobias Sekan			<tobias.sekan@startmail.com>
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
 *		\file		htdocs/user/group/perms.php
 * 		\brief		Page to set permissions of a user group record
 */

if (!defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1'); // Force use of CSRF protection with tokens even for GET
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by page
$langs->loadLangs(array('users', 'admin'));

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$module = GETPOST('module', 'alpha');
$rights = GETPOSTINT('rights');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'groupperms'; // To manage different context of search

if (!isset($id) || empty($id)) {
	accessforbidden();
}

// Define if user can read permissions
$permissiontoread = ($user->admin || $user->hasRight("user", "user", "read"));
// Define if user can modify group permissions
$permissiontoedit = ($user->admin || $user->hasRight("user", "user", "write"));
// Advanced permissions
$advancedpermsactive = false;
if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
	$advancedpermsactive = true;
	$permissiontoread = ($user->admin || ($user->hasRight("user", "group_advance", "read") && $user->hasRight("user", "group_advance", "readperms")));
	$permissiontoedit = ($user->admin || $user->hasRight("user", "group_advance", "write"));
}

// Security check
$socid = 0;
if (!empty($user->socid) && $user->socid > 0) {
	$socid = $user->socid;
}
//$result = restrictedArea($user, 'user', $id, 'usergroup', '');
if (!$permissiontoread) {
	accessforbidden();
}

$object = new UserGroup($db);
$object->fetch($id);
$object->loadRights();

$entity = $conf->entity;

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('groupperms', 'globalcard'));


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'addrights' && $permissiontoedit) {
		$editgroup = new UserGroup($db);
		$result = $editgroup->fetch($object->id);
		if ($result > 0) {
			$result = $editgroup->addrights($rights, $module, '', $entity);
			if ($result < 0) {
				setEventMessages($editgroup->error, $editgroup->errors, 'errors');
			}
		} else {
			dol_print_error($db);
		}

		$user->clearrights();
		$user->loadRights();
	}

	if ($action == 'delrights' && $permissiontoedit) {
		$editgroup = new UserGroup($db);
		$result = $editgroup->fetch($id);
		if ($result > 0) {
			$result = $editgroup->delrights($rights, $module, '', $entity);
			if ($result < 0) {
				setEventMessages($editgroup->error, $editgroup->errors, 'errors');
			}
		} else {
			dol_print_error($db);
		}

		$user->clearrights();
		$user->loadRights();
	}
}


/*
 * View
 */

$form = new Form($db);

$title = $object->name." - ".$langs->trans('Permissions');
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-group_perms');

if ($object->id > 0) {
	$head = group_prepare_head($object);
	$title = $langs->trans("Group");
	print dol_get_fiche_head($head, 'rights', $title, -1, 'group');

	// Charge les modules soumis a permissions
	$modules = array();
	$modulesdir = dolGetModulesDirs();

	$db->begin();

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
						}
					}
				}
			}
		}
	}

	$db->commit();

	// Read permissions of group
	$permsgroupbyentity = array();

	$sql = "SELECT DISTINCT r.id, r.libelle, r.module, gr.entity";
	$sql .= " FROM ".MAIN_DB_PREFIX."rights_def as r,";
	$sql .= " ".MAIN_DB_PREFIX."usergroup_rights as gr";
	$sql .= " WHERE gr.fk_id = r.id";
	$sql .= " AND gr.entity = ".((int) $entity);
	$sql .= " AND gr.fk_usergroup = ".((int) $object->id);

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
			array_push($permsgroupbyentity[$obj->entity], $obj->id);
			$i++;
		}
		$db->free($result);
	} else {
		dol_print_error($db);
	}

	/*
	 * Part to add/remove permissions
	 */

	$linkback = '<a href="'.DOL_URL_ROOT.'/user/group/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'id', $linkback, $user->hasRight("user", "user", "read") || $user->admin);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Name (already in dol_banner, we keep it to have the GlobalGroup picto, but we should move it in dol_banner)
	if (isModEnabled('multicompany')) {
		print '<tr><td class="titlefield">'.$langs->trans("Name").'</td>';
		print '<td class="valeur">'.dol_escape_htmltag($object->name);
		if (empty($object->entity)) {
			print img_picto($langs->trans("GlobalGroup"), 'redstar');
		}
		print "</td></tr>\n";
	}

	// Multicompany
	if (isModEnabled('multicompany') && is_object($mc) && !getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $conf->entity == 1 && $user->admin && !$user->entity) {
		$mc->getInfo($object->entity);
		print "<tr>".'<td class="titlefield">'.$langs->trans("Entity").'</td>';
		print '<td class="valeur">'.dol_escape_htmltag($mc->label);
		print "</td></tr>\n";
	}

	unset($object->fields['nom']); // Name already displayed in banner

	// Common attributes
	$keyforbreak = '';
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';

	print '<div class="clearboth"></div>';

	print '<br>';

	if ($user->admin) {
		print info_admin($langs->trans("WarningOnlyPermissionOfActivatedModules"));
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('insertExtraHeader', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	print "\n";
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Module").'</td>';
	if ($permissiontoedit) {
		print '<td class="center nowrap">';
		print '<a class="reposition commonlink" title="'.dol_escape_htmltag($langs->trans("All")).'" alt="'.dol_escape_htmltag($langs->trans("All")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addrights&token='.newToken().'&entity='.$entity.'&module=allmodules&confirm=yes">'.$langs->trans("All")."</a>";
		print '/';
		print '<a class="reposition commonlink" title="'.dol_escape_htmltag($langs->trans("None")).'" alt="'.dol_escape_htmltag($langs->trans("None")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delrights&&token='.newToken().'&entity='.$entity.'&module=allmodules&confirm=yes">'.$langs->trans("None")."</a>";
		print '</td>';
	} else {
		print '<td></td>';
	}
	print '<td class="center" width="24"></td>';
	//print '<td>'.$langs->trans("Permissions").'</td>';
	print '<td class="center"></td>';

	print '<td class="right nowrap">';
	print '<a class="showallperms" title="'.dol_escape_htmltag($langs->trans("ShowAllPerms")).'" alt="'.dol_escape_htmltag($langs->trans("ShowAllPerms")).'" href="#">'.img_picto('', 'folder-open', 'class="paddingright"').'<span class="hideonsmartphone">'.$langs->trans("ExpandAll").'</span></a>';
	print ' | ';
	print '<a class="hideallperms" title="'.dol_escape_htmltag($langs->trans("HideAllPerms")).'" alt="'.dol_escape_htmltag($langs->trans("HideAllPerms")).'" href="#">'.img_picto('', 'folder', 'class="paddingright"').'<span class="hideonsmartphone">'.$langs->trans("UndoExpandAll").'</span></a>';
	print '</td>';
	print '</tr>'."\n";

	$sql = "SELECT r.id, r.libelle as label, r.module, r.perms, r.subperms, r.module_position, r.bydefault";
	$sql .= " FROM ".MAIN_DB_PREFIX."rights_def as r";
	$sql .= " WHERE r.libelle NOT LIKE 'tou%'"; // On ignore droits "tous"
	$sql .= " AND r.entity = ".((int) $entity);
	if (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
		$sql .= " AND r.perms NOT LIKE '%_advance'"; // Hide advanced perms if option is disable
	}
	$sql .= " ORDER BY r.family_position, r.module_position, r.module, r.id";

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		$j = 0;
		$oldmod = '';

		$cookietohidegroup = (empty($_COOKIE["DOLUSER_PERMS_HIDE_GRP"]) ? '' : preg_replace('/^,/', '', $_COOKIE["DOLUSER_PERMS_HIDE_GRP"]));
		$cookietohidegrouparray = explode(',', $cookietohidegroup);

		while ($i < $num) {
			$obj = $db->fetch_object($result);

			// If line is for a module that does not exist anymore (absent of includes/module), we ignore it
			if (empty($modules[$obj->module])) {
				$i++;
				continue;
			}

			$objMod = $modules[$obj->module];

			if (GETPOSTISSET('forbreakperms_'.$obj->module)) {
				$ishidden = GETPOSTINT('forbreakperms_'.$obj->module);
			} elseif (in_array($j, $cookietohidegrouparray)) {	// If j is among list of hidden group
				$ishidden = 1;
			} else {
				$ishidden = 0;
			}
			$isexpanded = ! $ishidden;

			// Break found, it's a new module to catch
			if (isset($obj->module) && ($oldmod != $obj->module)) {
				$oldmod = $obj->module;

				$j++;
				if (GETPOSTISSET('forbreakperms_'.$obj->module)) {
					$ishidden = GETPOSTINT('forbreakperms_'.$obj->module);
				} elseif (in_array($j, $cookietohidegrouparray)) {	// If j is among list of hidden group
					$ishidden = 1;
				} else {
					$ishidden = 0;
				}
				$isexpanded = ! $ishidden;
				// Break detected, we get objMod
				$objMod = $modules[$obj->module];
				$picto = ($objMod->picto ? $objMod->picto : 'generic');

				// Show break line
				print '<tr class="oddeven trforbreakperms" data-hide-perms="'.$obj->module.'" data-j="'.$j.'">';
				// Picto and label of module
				print '<td class="maxwidthonsmartphone tdoverflowmax200 tdforbreakperms" data-hide-perms="'.$obj->module.'" title="'.dol_escape_htmltag($objMod->getName()).'">';
				print img_object('', $picto, 'class="pictoobjectwidth paddingright"').' '.$objMod->getName();
				print '<a name="'.$objMod->getName().'"></a>';
				print '</td>';
				// Permission and tick (2 columns)
				if ($permissiontoedit) {
					print '<td class="center wraponsmartphone">';
					print '<span class="permtohide_'.$obj->module.'" '.(!$isexpanded ? ' style="display:none"' : '').'>';
					print '<a class="reposition alink addexpandedmodulesinparamlist" title="'.dol_escape_htmltag($langs->trans("All")).'" alt="'.dol_escape_htmltag($langs->trans("All")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addrights&token='.newToken().'&entity='.$entity.'&module='.$obj->module.'&confirm=yes&updatedmodulename='.$obj->module.'">'.$langs->trans("All")."</a>";
					print ' / ';
					print '<a class="reposition alink addexpandedmodulesinparamlist" title="'.dol_escape_htmltag($langs->trans("None")).'" alt="'.dol_escape_htmltag($langs->trans("None")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delrights&token='.newToken().'&entity='.$entity.'&module='.$obj->module.'&confirm=yes&updatedmodulename='.$obj->module.'">'.$langs->trans("None")."</a>";
					print '</span>';
					print '</td>';
					print '<td class="tdforbreakperms" data-hide-perms="'.$obj->module.'">';
					print '</td>';
				} else {
					print '<td class="tdforbreakperms" data-hide-perms="'.$obj->module.'"></td>';
					print '<td class="tdforbreakperms" data-hide-perms="'.$obj->module.'"></td>';
				}
				// Description of permission (2 columns)
				print '<td class="tdforbreakperms" data-hide-perms="'.$obj->module.'"></td>';
				print '<td class="maxwidthonsmartphone right tdforbreakperms" data-hide-perms="'.$obj->module.'">';
				print '<div class="switchfolderperms folderperms_'.$obj->module.'"'.($isexpanded ? ' style="display:none;"' : '').'>';
				print img_picto('', 'folder', 'class="marginright"');
				print '</div>';
				print '<div class="switchfolderperms folderopenperms_'.$obj->module.'"'.(!$isexpanded ? ' style="display:none;"' : '').'>';
				print img_picto('', 'folder-open', 'class="marginright"');
				print '</div>';
				print '</td>'; //Add picto + / - when open en closed
				print '</tr>'."\n";
			}

			print '<!-- '.$obj->module.'->'.$obj->perms.($obj->subperms ? '->'.$obj->subperms : '').' -->'."\n";
			print '<tr class="oddeven trtohide_'.$obj->module.'"'.(!$isexpanded ? ' style="display:none"' : '').'>';


			// Picto and label of module
			print '<td class="maxwidthonsmartphone tdoverflowmax200">';
			print '<input type="hidden" name="forbreakperms_'.$obj->module.'" id="idforbreakperms_'.$obj->module.'" css="cssforfieldishiden" data-j="'.$j.'" value="'.($isexpanded ? '0' : "1").'">';
			//print img_object('', $picto, 'class="inline-block pictoobjectwidth"').' '.$objMod->getName();
			print '</td>';

			// Permission and tick (2 columns)
			if (!empty($permsgroupbyentity[$entity]) && is_array($permsgroupbyentity[$entity])) {
				if (in_array($obj->id, $permsgroupbyentity[$entity])) {
					// Own permission by group
					if ($permissiontoedit) {
						print '<td class="center"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delrights&token='.newToken().'&entity='.$entity.'&rights='.$obj->id.'&confirm=yes">';
						//print img_edit_remove($langs->trans("Remove"));
						print img_picto($langs->trans("Remove"), 'switch_on');
						print '</a></td>';
					}
					print '<td class="center nowrap">';
					print img_picto($langs->trans("Active"), 'tick');
					print '</td>';
				} else {
					// Do not own permission
					if ($permissiontoedit) {
						print '<td class="center"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addrights&token='.newToken().'&entity='.$entity.'&rights='.$obj->id.'&confirm=yes">';
						//print img_edit_add($langs->trans("Add"));
						print img_picto($langs->trans("Add"), 'switch_off');
						print '</a></td>';
					}
					print '<td>&nbsp;</td>';
				}
			} else {
				// Do not own permission
				if ($permissiontoedit) {
					print '<td class="center"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addrights&entity='.$entity.'&rights='.$obj->id.'&confirm=yes&token='.newToken().'">';
					//print img_edit_add($langs->trans("Add"));
					print img_picto($langs->trans("Add"), 'switch_off');
					print '</a></td>';
				} else {
					print '<td>&nbsp;</td>';
				}
				print '<td>&nbsp;</td>';
			}

			// Description of permission (2 columns)
			$permlabel = (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && ($langs->trans("PermissionAdvanced".$obj->id) != "PermissionAdvanced".$obj->id) ? $langs->trans("PermissionAdvanced".$obj->id) : (($langs->trans("Permission".$obj->id) != "Permission".$obj->id) ? $langs->trans("Permission".$obj->id) : $langs->trans($obj->label)));
			print '<td>';
			print $permlabel;
			$idtouse = $obj->id;
			if (in_array($idtouse, array(121, 122, 125, 126))) {	// Force message for the 3 permission on third parties
				$idtouse = 122;
			}
			if ($langs->trans("Permission".$idtouse.'b') != "Permission".$idtouse.'b') {
				print '<br><span class="opacitymedium">'.$langs->trans("Permission".$idtouse.'b').'</span>';
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
				$htmltext .= '<br>'.$langs->trans("Permission").': user->hasRight(\''.$obj->module.'\', \''.$obj->perms.'\''.($obj->subperms ? ', \''.$obj->subperms.'\'' : '').')';
				print $form->textwithpicto('', $htmltext);
				//print '<span class="opacitymedium">'.$obj->id.'</span>';
				print '</td>';
			} else {
				print '<td>&nbsp;</td>';
			}

			print '</tr>'."\n";

			$i++;
		}
	}
	print '</table>';
	print '</div>';

	print '<script>';
	print '$(".tdforbreakperms:not(.alink)").on("click", function(){
		console.log("Click on tdforbreakperms");
		moduletohide = $(this).data("hide-perms");
		j = $(this).data("j");
		if ($("#idforbreakperms_"+moduletohide).val() == 1) {
			console.log("idforbreakperms_"+moduletohide+" has value hidden=1");
			$(".trtohide_"+moduletohide).show();
			$(".permtoshow_"+moduletohide).hide();
			$(".permtohide_"+moduletohide).show();
			$(".folderperms_"+moduletohide).hide();
			$(".folderopenperms_"+moduletohide).show();
			$("#idforbreakperms_"+moduletohide).val("0");
		} else {
			console.log("idforbreakperms_"+moduletohide+" has value hidden=0");
			$(".trtohide_"+moduletohide).hide();
			$(".folderopenperms_"+moduletohide).hide();
			$(".folderperms_"+moduletohide).show();
			$(".permtoshow_"+moduletohide).show();
			$(".permtohide_"+moduletohide).hide();
			$("#idforbreakperms_"+moduletohide).val("1");
		}

		// Now rebuild the value for cookie
		var hideuserperm="";
		$(".trforbreakperms").each(function(index) {
			//console.log( index + ": " + $( this ).data("j") + " " + $( this ).data("hide-perms") + " " + $("input[data-j="+(index+1)+"]").val());
			if ($("input[data-j="+(index+1)+"]").val() == 1) {
				hideuserperm=hideuserperm+","+(index+1);
			}
		});
		// set cookie by js
		date = new Date(); date.setTime(date.getTime()+(30*86400000));
		if (hideuserperm) {
			console.log("set cookie DOLUSER_PERMS_HIDE_GRP="+hideuserperm);
			document.cookie = "DOLUSER_PERMS_HIDE_GRP=" + hideuserperm + "; expires=" + date.toGMTString() + "; path=/ ";
		} else {
			console.log("delete cookie DOLUSER_PERMS_HIDE_GRP");
			document.cookie = "DOLUSER_PERMS_HIDE_GRP=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/ ";
		}
	});';

	// Button expand / collapse all
	print '$(".showallperms").on("click", function(){
		console.log("Click on showallperms");

		console.log("delete cookie DOLUSER_PERMS_HIDE_GRP from showallperms click");
		document.cookie = "DOLUSER_PERMS_HIDE_GRP=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/ ";
		$(".tdforbreakperms").each( function(){
			moduletohide = $(this).data("hide-perms");
			//console.log(moduletohide);
			if ($("#idforbreakperms_"+moduletohide).val() != 0) {
				$(this).trigger("click");	// emulate the click, so the cooki will be resaved
			}
		})
	});

	$(".hideallperms").on("click", function(){
		console.log("Click on hideallperms");

		$(".tdforbreakperms").each( function(){
			moduletohide = $(this).data("hide-perms");
			//console.log(moduletohide);
			if ($("#idforbreakperms_"+moduletohide).val() != 1) {
				$(this).trigger("click");	// emulate the click, so the cooki will be resaved
			}
		})
	});';
	print "\n";
	print '</script>';

	print '<style>';
	print '.switchfolderperms{
		cursor: pointer;
	}';
	print '</style>';
	print '</div>';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('insertExtraFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
