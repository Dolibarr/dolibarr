<?php
/* Copyright (C) 2005-2017  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2015  Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2013	    Florian Henry               <florian.henry@open-concept.pro.com>
 * Copyright (C) 2018       Ferran Marcet               <fmarcet@2byte.es>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/user/api_toke/card.php
 *       \brief      Page to show user token and corresponding perm
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by page
$langs->loadLangs(array('admin', 'users', 'errors'));
$error = 0;

// Security check
$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');

if (empty($id) && $action != 'add' && $action != 'create') {
	accessforbidden();
}

$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->hasRight("user", "self", "write")) ? '' : 'user');

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

// $user is current user, $id is id of edited user
$canreaduser = ($user->admin || ($user->id == $id));
$canedittoken = ($user->admin || (($user->id == $id) && $user->hasRight("user", "self", "write")));

if (!$canreaduser) {
	accessforbidden();
}

// Retrieve needed GETPOSTS for this file
$toselect = GETPOST('toselect', 'array');
$tokenid = GETPOST('tokenid', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$module = GETPOST('module', 'alpha');
$rights = GETPOSTINT('rights');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// SQL query to retrieve the selected token
$sql = "SELECT oat.rowid as token_id, oat.token, oat.entity, oat.state as rights, oat.datec as date_creation, oat.tms as date_modification";
if (isModEnabled('multicompany')) {
	$sql .= ", e.label";
}
$sql .= " FROM ".MAIN_DB_PREFIX."oauth_token as oat";
if (isModEnabled('multicompany')) {
	$sql .= " JOIN ".$db->prefix()."entity as e ON oat.entity = e.rowid";
}
$sql .= " WHERE oat.rowid = ".((int) $tokenid);

$resql = $db->query($sql);

$object = new User($db);
$object->fetch($id, '', '', 1);
$object->loadRights();

// Deny access if user not using api
if (empty($object->api_key)) {
	accessforbidden();
}

$form = new Form($db);
$token = $db->fetch_object($resql);

$entity = $conf->entity;
$mc = $mc ?? "";

/*
 * Actions
 */

$parameters = array('id' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if (empty($backtopage)) {
		$backtopage = 'list.php?id='.$object->id;
	}

	if ($cancel) {
		if (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	// Get perms lost by losing chosen perm in $rights
	// or get all perms by module
	if (in_array($action, array('addrights', 'delrights'))) {
		$rigthsarray = [];

		if ((strlen($token->rights) == 1 && substr($token->rights, 0, 1) == 0)) {
			// If the string only contains "0", we empty it so that we store the rights in state without the 0
			$token->rights = "";
		} elseif (empty($token->rights)) {
			// If we delete a perm from a token with empty state (meaning all user perms) and not "0"
			// we add all perms from the user before ungranting selected ones
			// Users perms
			$sql = "SELECT ur.fk_id";
			$sql .= " FROM ".MAIN_DB_PREFIX."user_rights as ur";
			$sql .= " WHERE ur.entity = ".((int) $token->entity);
			$sql .= " AND ur.fk_user = ".((int) $object->id);
			$sql .= " UNION ";
			// Groups perms
			$sql .= "SELECT gr.fk_id";
			$sql .= " FROM ".MAIN_DB_PREFIX."usergroup_rights as gr";
			$sql .= " WHERE gr.entity = ".((int) $token->entity);
			$sql .= " AND EXISTS(SELECT gu.rowid FROM llx_usergroup_user as gu WHERE gu.fk_user = ".((int) $id)." AND gu.fk_usergroup = gr.fk_usergroup)";

			dol_syslog("get user perms", LOG_DEBUG);
			$result = $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);
				$i = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($result);
					$token->rights .= $obj->fk_id.",";
					$i++;
				}
				$db->free($result);
			} else {
				dol_print_error($db);
			}
			$token->rights = rtrim($token->rights, ",");
		}

		if (!empty($rights)) {
			$module = $perms = $subperms = '';

			$sql = "SELECT module, perms, subperms";
			$sql .= " FROM ".$db->prefix()."rights_def";
			$sql .= " WHERE id = ".((int) $rights);
			$sql .= " AND entity = ".((int) $token->entity);

			$result = $db->query($sql);
			if ($result) {
				$obj = $db->fetch_object($result);

				if ($obj) {
					$module = $obj->module;
					$perms = $obj->perms;
					$subperms = $obj->subperms;
				}
			} else {
				dol_print_error($db);
			}

			$sql = "SELECT id";
			$sql .= " FROM ".$db->prefix()."rights_def";
			$sql .= " WHERE entity = ".((int) $token->entity);
			$sql .= " AND (id=".((int) $rights);
			if ($action == 'addrights') {
				if (!empty($subperms)) {
					$sql .= " OR (module='".$db->escape($module)."' AND perms='".$db->escape($perms)."' AND (subperms='lire' OR subperms='read'))";
				} elseif (!empty($perms)) {
					$sql .= " OR (module='".$db->escape($module)."' AND (perms='lire' OR perms='read') AND (subperms IS NULL or subperms = ''))";
				}
			} elseif ($action == 'delrights') {
				if ($subperms == 'lire' || $subperms == 'read') {
					$sql .= " OR (module='".$db->escape($module)."' AND perms='".$db->escape($perms)."' AND subperms IS NOT NULL)";
				}
				if ($perms == 'lire' || $perms == 'read') {
					$sql .= " OR (module='".$db->escape($module)."')";
				}
			}
			$sql .= ")";
			// To avoid better perms in token
			$sql .= " AND id IN (";
			$sql .= " SELECT ur.fk_id";
			$sql .= " FROM llx_user_rights as ur";
			$sql .= " WHERE ur.entity = ".((int) $token->entity);
			$sql .= " AND ur.fk_user = ".((int) $id);
			$sql .= " UNION";
			$sql .= " SELECT gr.fk_id";
			$sql .= " FROM llx_usergroup_rights as gr";
			$sql .= " WHERE EXISTS (SELECT gu.rowid FROM llx_usergroup_user as gu WHERE gu.fk_user = ".((int) $id);
			$sql .= " AND gu.fk_usergroup = gr.fk_usergroup))";

			$resql = $db->query($sql);
			while ($obj = $db->fetch_object($resql)) {
				$rigthsarray []= $obj->id;
			}
		} elseif (!empty($module)) {
			$sql = "SELECT id";
			$sql .= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql .= " WHERE entity IN (".$db->sanitize($token->entity, 0, 0, 0, 0).")";
			if ($module != 'allmodules') {
				$sql .= " AND (module='".$db->escape($module)."')";
			}
			// To enable only all perms in a module that a user has to avoid better perms in token
			$sql .= " AND id IN (";
			$sql .= " SELECT ur.fk_id";
			$sql .= " FROM llx_user_rights as ur";
			$sql .= " WHERE ur.entity = ".((int) $token->entity);
			$sql .= " AND ur.fk_user = ".((int) $id);
			$sql .= " UNION";
			$sql .= " SELECT gr.fk_id";
			$sql .= " FROM llx_usergroup_rights as gr";
			$sql .= " WHERE EXISTS(SELECT gu.rowid FROM llx_usergroup_user as gu WHERE gu.fk_user = ".((int) $id);
			$sql .= " AND gu.fk_usergroup = gr.fk_usergroup))";
			$resql = $db->query($sql);
			while ($obj = $db->fetch_object($resql)) {
				$rigthsarray []= $obj->id;
			}
		}
	}

	if ($action == 'addrights' && $canedittoken && $confirm == 'yes') {
		$tokenrigthsarray = [];

		if (!empty($token->rights)) {
			$tokenrigthsarray = explode(',', $token->rights);
		}

		if (isset($rigthsarray)) {
			$tokenrigthsarray = array_merge($tokenrigthsarray, $rigthsarray);
		} else {
			$tokenrigthsarray []= $rights;
		}

		$tokenrigthsarray = array_unique($tokenrigthsarray);
		sort($tokenrigthsarray);
		$newrigths = preg_replace('/\s+/', '', implode(',', $tokenrigthsarray));

		$sql = "UPDATE ".MAIN_DB_PREFIX."oauth_token";
		$sql.= " SET state = '".$db->escape($newrigths)."'";
		$sql.= ", tms = '".$db->idate(dol_now())."'";
		$sql.= " WHERE rowid = ".((int) $tokenid);

		$resql = $db->query($sql);
		if (!$resql) {
			dol_print_error($db);
		}

		$reloadtoken = true;
	}

	if ($action == 'delrights' && $canedittoken && $confirm == 'yes') {
		$tokenrigthsarray = explode(',', $token->rights);
		if (isset($rigthsarray)) {
			$tokenrigthsarray = array_diff($tokenrigthsarray, $rigthsarray);
		} else {
			$tokenrigthsarray = array_diff($tokenrigthsarray, array($rights));
		}

		if (count($tokenrigthsarray) == 0) {
			$tokenrigthsarray = array(0);
		}

		$tokenrigthsarray = array_unique($tokenrigthsarray);
		sort($tokenrigthsarray);
		$newrigths = preg_replace('/\s+/', '', implode(',', $tokenrigthsarray));

		$sql = "UPDATE ".MAIN_DB_PREFIX."oauth_token";
		$sql.= " SET state = '".$db->escape($newrigths)."'";
		$sql.= ", tms = '".$db->idate(dol_now())."'";
		$sql.= " WHERE rowid = ".((int) $tokenid);

		$resql = $db->query($sql);
		if (!$resql) {
			dol_print_error($db);
		}

		$reloadtoken = true;
	}

	if ($action == 'add') {
		$tokenstring = GETPOST('api_key', 'alphanohtml');
		$userid = GETPOSTINT('user');
		$useridtoadd = !empty($userid) && $userid > 0 ? $userid : $id;

		if (empty($tokenstring)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Token")), null, 'errors');
			$action = 'create';
			$error++;
		}

		if (empty($useridtoadd)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("User")), null, 'errors');
			$action = 'create';
			$error++;
		}

		// Check if a token already exists for the dolibarr api service duplicates
		$nbtotalofrecords = '';
		$sqlforcount = 'SELECT COUNT(*) as nbtotalofrecords';
		$sqlforcount .= " FROM ".MAIN_DB_PREFIX."oauth_token as oat";
		$sqlforcount .= " WHERE token = '".$db->escape(dolEncrypt($tokenstring, '', '', 'dolibarr'))."'";
		$sqlforcount .= " AND service = 'dolibarr_rest_api'";
		$resql = $db->query($sqlforcount);
		if ($resql) {
			$objforcount = $db->fetch_object($resql);
			$nbtotalofrecords = $objforcount->nbtotalofrecords;
		} else {
			dol_print_error($db);
			$error++;
		}

		if (isset($nbtotalofrecords) && $nbtotalofrecords > 0) {
			setEventMessages($langs->trans("ErrorFieldExist", $langs->transnoentitiesnoconv("Token")), null, 'errors');
			$action = 'create';
			$error++;
		}

		if (!$error) {
			$db->begin();

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."oauth_token (service, token, state, fk_user, entity, datec)";
			$sql .= " VALUES ('dolibarr_rest_api', '".$db->escape(dolEncrypt($tokenstring, '', '', 'dolibarr'))."', 0, ".((int) $useridtoadd).", ".((int) $entity).", '".$db->idate(dol_now())."')";
			$resql = $db->query($sql);

			if (!$resql) {
				dol_print_error($db);
				$db->rollback();
			} else {
				$insertedtokenid = $db->last_insert_id(MAIN_DB_PREFIX."oauth_token");
				$db->commit();
				header("Location: ".$_SERVER["PHP_SELF"].'?id='.$useridtoadd.'&tokenid='.$insertedtokenid);
				exit;
			}
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes' && $canedittoken) {
		// Remove token
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."oauth_token";
		$sql .= " WHERE rowid = ".((int) $tokenid;

		$resql = $db->query($sql);

		if ($resql) {
			header('Location: list.php?id='.$object->id);
			exit;
		} else {
			dol_print_error($db);
		}
	}
}

if (isset($reloadtoken)) { // If we add or del rights, we want to refresh the token with its new updated fields
	$sql = "SELECT oat.rowid as token_id, oat.token, oat.entity, oat.state as rights, oat.datec as date_creation, oat.tms as date_modification";
	if (isModEnabled('multicompany')) {
		$sql .= ", e.label";
	}
	$sql .= " FROM ".MAIN_DB_PREFIX."oauth_token as oat";
	if (isModEnabled('multicompany')) {
		$sql .= " JOIN ".$db->prefix()."entity as e ON oat.entity = e.rowid";
	}
	$sql .= " WHERE oat.rowid = ".((int) $tokenid);

	$resql = $db->query($sql);
	$token = $db->fetch_object($resql);
	$tokenvalue = dolDecrypt($token->token);
}

/*
 * View
 */

if ($object->id > 0) {
	$person_name = !empty($object->firstname) ? $object->lastname.", ".$object->firstname : $object->lastname;
	$title = $person_name." - ".$langs->trans('ApiTokens');
} else {
	$title = $langs->trans("NewToken");
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-card_param_ihm');

$formconfirm = '';

if ($action == 'delete') {
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&tokenid='.$token->token_id, $langs->trans('DeleteToken'), $langs->trans('ConfirmDeleteToken'), 'confirm_delete', '', 0, 1);
}

print $formconfirm;

if ($action == 'create') {
	print load_fiche_titre($title, '', 'user');
	print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate">';

	if ($user->admin && empty($id)) {
		print '<tr class="field_ref"><td class="titlefieldcreate fieldrequired">'.$langs->trans('User').'</td>';
		print '<td class="valuefieldcreate">';
		print $form->select_dolusers('', 'user', 1, null, 0, '', '', (string) $object->entity, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
		print '</td></tr>';
	} else {
		print '<tr class="field_ref"><td class="titlefieldcreate fieldrequired">'.$langs->trans('User').'</td><td class="valuefieldcreate">'.($person_name ?? '').'</td></tr>';
	}

	if (isModEnabled('multicompany') && is_object($mc)) {
		$mc->getInfo($entity);
		print '<tr class="field_ref"><td class="titlefieldcreate fieldrequired">'.$langs->trans('Entity').'</td><td class="valuefieldcreate">'.$mc->label.'</td></tr>';
	}

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Token").'</td>';
	print '<td>';
	print '<input class="minwidth300 maxwidth400 widthcentpercentminusx" minlength="12" maxlength="128" type="text" id="api_key" name="api_key" value="'.GETPOST('api_key', 'alphanohtml').'" autocomplete="off">';
	if (!empty($conf->use_javascript_ajax)) {
		print img_picto($langs->transnoentities('Generate'), 'refresh', 'id="generate_api_key" class="linkobject paddingleft"');
	}
	print '</td></tr>';
	print "</table>\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input class="button" name="add" value="'.$langs->trans("Create").'" type="submit">';
	print '<input class="button button-cancel" value="'.$langs->trans("Cancel").'" name="cancel" type="submit">';
	print '</div>';

	print "</form>";
} elseif ($id > 0 && !empty($token)) {
	$arrayofselected = is_array($toselect) ? $toselect : array();

	$head = user_prepare_head($object);

	$title = $langs->trans("User");

	print dol_get_fiche_head($head, 'apitoken', $title, -1, 'user');

	$tokenvalue = dolDecrypt($token->token);

	$linkback  = '<a href="'.DOL_URL_ROOT.'/user/api_token/list.php?id='.$id.'">'.$langs->trans("BackToTokenList").'</a>';
	$linkback .= '<a href="'.DOL_URL_ROOT.'/user/list.php">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'&output=file&file='.urlencode(dol_sanitizeFileName($object->getFullName($langs).'.vcf')).'" class="refid" rel="noopener">';
	$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
	$morehtmlref .= '</a>';

	$urltovirtualcard = '/user/virtualcard.php?id='.((int) $object->id);
	$morehtmlref .= dolButtonToOpenUrlInDialogPopup('publicvirtualcard', $langs->transnoentitiesnoconv("PublicVirtualCardUrl").' - '.$object->getFullName($langs), img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'), $urltovirtualcard, '', 'nohover');

	dol_banner_tab($object, 'api_token_card', $linkback, $user->admin, 'rowid', 'ref', $morehtmlref);

	// Tokens info
	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Login
	print '<tr><td class="titlefield">'.$langs->trans("Login").'</td>';
	if (!empty($object->ldap_sid) && $object->status == 0) {
		print '<td class="error">';
		print $langs->trans("LoginAccountDisableInDolibarr");
		print '</td>';
	} else {
		print '<td>';
		$addadmin = '';
		if (property_exists($object, 'admin')) {
			if (isModEnabled('multicompany') && !empty($object->admin) && empty($object->entity)) {
				$addadmin .= img_picto($langs->trans("SuperAdministratorDesc"), "redstar", 'class="paddingleft valignmiddle"');
			} elseif (!empty($object->admin)) {
				$addadmin .= img_picto($langs->trans("AdministratorDesc"), "star", 'class="paddingleft valignmiddle"');
			}
		}
		print showValueWithClipboardCPButton($object->login).$addadmin;
		print '</td>';
	}
	print '</tr>'."\n";

	// Token
	print '<tr><td class="titlefield">'.$langs->trans("Token").'</td>';
	print '<td>';
	print showValueWithClipboardCPButton($tokenvalue, 1, $tokenvalue);
	print '</td>';
	print '</tr>'."\n";

	// Entity
	if (isModEnabled('multicompany') && is_object($mc)) {
		$mc->getInfo($token->entity);
		print '<tr><td class="titlefield">'.$langs->trans("Entity").'</td>';
		print '<td>';
		print '<span class="multicompany-entity-container">';
		print '<span class="fa fa-globe multicompany-button-template" title="'.$langs->trans("Entity").'"></span>';
		print $mc->label;
		print '&nbsp;</span>';
		print '</td>';
		print '</tr>'."\n";
	}

	// Creation date
	print '<tr><td class="titlefield">'.$langs->trans("DateCreation").'</td>';
	print '<td>';
	print dol_print_date($db->jdate($token->date_creation), 'dayhour');
	print '</td>';
	print '</tr>'."\n";

	// Modification date
	print '<tr><td class="titlefield">'.$langs->trans("DateModification").'</td>';
	print '<td>';
	print dol_print_date($db->jdate($token->date_modification), 'dayhour');
	print '</td>';
	print '</tr>'."\n";

	print '</table>';
	print '<div class="tabsAction">';
	print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&tokenid='.$token->token_id.'&action=delete&token='.newToken(), '', $canedittoken);
	print '</div>';
	print '</div>';

	print dol_get_fiche_end();

	print load_fiche_titre($langs->trans("ListOfRightsForToken"), '', 'fa-at');

	print '<!-- Rights section -->'."\n";

	if ($user->admin) {
		print info_admin($langs->trans("WarningOnlyPermissionOfActivatedModules"));
	}

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
							$ret = $objMod->insert_permissions(0, $token->entity);
							$modules[$objMod->rights_class] = $objMod;
							//print "modules[".$objMod->rights_class."]=$objMod;";
						}
					}
				}
			}
		}
	}

	$db->commit();

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Module").'</td>';
	if ($canedittoken) {
		print '<td class="center nowrap">';
		print '<a class="reposition commonlink addexpandedmodulesinparamlist" title="'.dol_escape_htmltag($langs->trans("All")).'" alt="'.dol_escape_htmltag($langs->trans("All")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&tokenid='.$tokenid.'&action=addrights&token='.newToken().'&module=allmodules&confirm=yes">'.$langs->trans("All")."</a>";
		print ' / ';
		print '<a class="reposition commonlink addexpandedmodulesinparamlist" title="'.dol_escape_htmltag($langs->trans("None")).'" alt="'.dol_escape_htmltag($langs->trans("None")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&tokenid='.$tokenid.'&action=delrights&token='.newToken().'&module=allmodules&confirm=yes">'.$langs->trans("None")."</a>";
		print '</td>';
	} else {
		print '<td></td>';
	}
	print '<td></td>';
	//print '<td></td>';
	print '<td class="right nowrap" colspan="2">';
	print '<a class="showallperms" title="'.dol_escape_htmltag($langs->trans("ShowAllPerms")).'" alt="'.dol_escape_htmltag($langs->trans("ShowAllPerms")).'" href="#">'.img_picto('', 'folder-open', 'class="paddingright"').'<span class="hideonsmartphone">'.$langs->trans("ExpandAll").'</span></a>';
	print ' | ';
	print '<a class="hideallperms" title="'.dol_escape_htmltag($langs->trans("HideAllPerms")).'" alt="'.dol_escape_htmltag($langs->trans("HideAllPerms")).'" href="#">'.img_picto('', 'folder', 'class="paddingright"').'<span class="hideonsmartphone">'.$langs->trans("UndoExpandAll").'</span></a>';
	print '</td>';
	print '</tr>'."\n";

	// Load modules rights and correct position if needed
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
			if (isModEnabled("reception")) {
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
			if (empty($obj->module_position) || ($objMod->isCoreOrExternalModule() == 'external' && $obj->module_position < 100000)) {
				if ($modules[$obj->module]->module_position > 0) {
					// TODO Define familyposition
					//$familyposition = $modules[$obj->module]->family_position;
					$familyposition = 0;

					$newmoduleposition = $modules[$obj->module]->module_position;

					// Correct $newmoduleposition position for external modules
					$objMod = $modules[$obj->module];
					if ($objMod->isCoreOrExternalModule() == 'external' && $newmoduleposition < 100000) {
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

	//Load perms ids for user and user's groups to show only the perms the user has and avoid better perms in the token.
	$allusersperms = array();

	// Users perms
	$sql = "SELECT ur.fk_id";
	$sql .= " FROM ".MAIN_DB_PREFIX."user_rights as ur";
	$sql .= " WHERE ur.entity = ".((int) $token->entity);
	$sql .= " AND ur.fk_user = ".((int) $object->id);
	$sql .= " UNION ";
	// Groups perms
	$sql .= "SELECT gr.fk_id";
	$sql .= " FROM ".MAIN_DB_PREFIX."usergroup_rights as gr";
	$sql .= " WHERE gr.entity = ".((int) $token->entity);
	$sql .= " AND EXISTS(SELECT gu.rowid FROM llx_usergroup_user as gu WHERE gu.fk_user = ".((int) $id)." AND gu.fk_usergroup = gr.fk_usergroup)";

	dol_syslog("get user perms", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($result);
			array_push($allusersperms, $obj->fk_id);
			$i++;
		}
		$db->free($result);
	} else {
		dol_print_error($db);
	}

	// Load perms ids for the user
	if (empty($token->rights) && !(strlen($token->rights) == 1 && substr($token->rights, 0, 1) == 0)) {
		$tokenperms = $allusersperms;
	} else {
		$tokenperms = explode(",", $token->rights);
	}

	// Load and show all the perms grouped by module
	if (count($allusersperms) > 0) {
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
			$j = 0;
			$oldmod = '';

			$cookietohidegroup = (empty($_COOKIE["DOLUSER_PERMS_HIDE_GRP"]) ? '' : preg_replace('/^,/', '', $_COOKIE["DOLUSER_PERMS_HIDE_GRP"]));
			$cookietohidegrouparray = explode(',', $cookietohidegroup);
			//var_dump($cookietohidegrouparray);

			while ($i < $num) {
				$obj = $db->fetch_object($result);

				// If line is for a module that does not exist anymore (absent of includes/module), we ignore it
				if (empty($modules[$obj->module])) {
					$i++;
					continue;
				}

				// Special cases
				if (isModEnabled("reception")) {
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

				if (GETPOSTISSET('forbreakperms_'.$obj->module)) {
					$ishidden = GETPOSTINT('forbreakperms_'.$obj->module);
				} elseif (in_array($j, $cookietohidegrouparray)) {    // If j is among list of hidden group
					$ishidden = 1;
				} else {
					$ishidden = 0;
				}
				$isexpanded = !$ishidden;
				//var_dump("isexpanded=".$isexpanded);

				$permsgroupbyentitypluszero = array();
				if (!empty($permsgroupbyentity[0])) {
					$permsgroupbyentitypluszero = array_merge($permsgroupbyentitypluszero, $permsgroupbyentity[0]);
				}
				if (!empty($permsgroupbyentity[$entity])) {
					$permsgroupbyentitypluszero = array_merge($permsgroupbyentitypluszero, $permsgroupbyentity[$entity]);
				}
				//var_dump($permsgroupbyentitypluszero);

				// Break found, it's a new module to catch
				if (isset($obj->module) && ($oldmod != $obj->module)) {
					$oldmod = $obj->module;

					$j++;
					if (GETPOSTISSET('forbreakperms_'.$obj->module)) {
						$ishidden = GETPOSTINT('forbreakperms_'.$obj->module);
					} elseif (in_array($j, $cookietohidegrouparray)) {    // If j is among list of hidden group
						$ishidden = 1;
					} else {
						$ishidden = 0;
					}
					$isexpanded = !$ishidden;
					//var_dump('$obj->module='.$obj->module.' isexpanded='.$isexpanded);

					// Break detected, we get objMod
					$objMod = $modules[$obj->module];
					$picto = ($objMod->picto ? $objMod->picto : 'generic');

					// Show break line
					print '<tr class="oddeven trforbreakperms trforbreaknobg" data-hide-perms="'.$obj->module.'" data-j="'.$j.'">';
					// Picto and label of module
					print '<td class="maxwidthonsmartphone tdoverflowmax200 tdforbreakperms" data-hide-perms="'.dol_escape_htmltag($obj->module).'" title="'.dol_escape_htmltag($objMod->getName()).'">';
					print '<input type="hidden" name="forbreakperms_'.$obj->module.'" id="idforbreakperms_'.$obj->module.'" css="cssforfieldishiden" data-j="'.$j.'" value="'.($isexpanded ? '0' : "1").'">';
					print img_object('', $picto, 'class="pictoobjectwidth paddingright"').' '.$objMod->getName();
					print '<a name="'.$objMod->getName().'"></a>';
					print '</td>';

					// Permission and tick (2 columns)
					if (($canedittoken && empty($objMod->rights_admin_allowed)) || empty($object->admin)) {
						if ($canedittoken) {
							print '<td class="tdforbreakperms tdforbreakpermsifnotempty center width50 nowraponall" data-hide-perms="'.dol_escape_htmltag($obj->module).'">';
							print '<span class="permtohide_'.dol_escape_htmltag($obj->module).'" '.(!$isexpanded ? ' style="display:none"' : '').'>';
							print '<a class="reposition alink addexpandedmodulesinparamlist" title="'.dol_escape_htmltag($langs->trans("All")).'" alt="'.dol_escape_htmltag($langs->trans("All")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&tokenid='.$tokenid.'&action=addrights&token='.newToken().'&module='.$obj->module.'&confirm=yes">'.$langs->trans("All")."</a>";
							print ' / ';
							print '<a class="reposition alink addexpandedmodulesinparamlist" title="'.dol_escape_htmltag($langs->trans("None")).'" alt="'.dol_escape_htmltag($langs->trans("None")).'" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&tokenid='.$tokenid.'&action=delrights&token='.newToken().'&module='.$obj->module.'&confirm=yes&">'.$langs->trans("None")."</a>";
							print '</span>';
							print '</td>';
							print '<td class="tdforbreakperms" data-hide-perms="'.dol_escape_htmltag($obj->module).'">';
							print '</td>';
						} else {
							print '<td class="tdforbreakperms" data-hide-perms="'.dol_escape_htmltag($obj->module).'"></td>';
							print '<td class="tdforbreakperms" data-hide-perms="'.dol_escape_htmltag($obj->module).'"></td>';
						}
					} else {
						if ($canedittoken) {
							print '<td class="tdforbreakperms center wraponsmartphone" data-hide-perms="'.dol_escape_htmltag($obj->module).'">';
							print '</td>';
							print '<td class="tdforbreakperms" data-hide-perms="'.dol_escape_htmltag($obj->module).'">';
							print '</td>';
						} else {
							print '<td class="right tdforbreakperms" data-hide-perms="'.dol_escape_htmltag($obj->module).'"></td>';
							print '<td class="tdforbreakperms" data-hide-perms="'.dol_escape_htmltag($obj->module).'"></td>';
						}
					}

					// Description of permission (2 columns)
					print '<td class="tdforbreakperms" data-hide-perms="'.dol_escape_htmltag($obj->module).'"></td>';
					print '<td class="maxwidthonsmartphone right tdforbreakperms" data-hide-perms="'.dol_escape_htmltag($obj->module).'">';

					print '<div class="switchfolderperms inline-block marginrightonly folderperms_'.dol_escape_htmltag($obj->module).'"'.($isexpanded ? ' style="display:none;"' : '').'>';
					print img_picto('', 'folder', 'class="marginright"');
					print '</div>';
					print '<div class="switchfolderperms inline-block marginrightonly folderopenperms_'.dol_escape_htmltag($obj->module).'"'.(!$isexpanded ? ' style="display:none;"' : '').'>';
					print img_picto('', 'folder-open', 'class="marginright"');
					print '</div>';

					print '</td>'; //Add picto + / - when open en closed
					print '</tr>'."\n";
				}

				$permlabel = (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && ($langs->trans("PermissionAdvanced".$obj->id) != "PermissionAdvanced".$obj->id) ? $langs->trans("PermissionAdvanced".$obj->id) : (($langs->trans("Permission".$obj->id) != "Permission".$obj->id) ? $langs->trans("Permission".$obj->id) : $langs->trans($obj->label)));

				print '<!-- '.$obj->module.'->'.$obj->perms.($obj->subperms ? '->'.$obj->subperms : '').' -->'."\n";
				print '<tr class="oddeven trtohide_'.$obj->module.'"'.(!$isexpanded ? ' style="display:none"' : '').'>';

				// Picto and label of module
				print '<td class="maxwidthonsmartphone">';
				print '</td>';

				// Permission and tick (2 columns)
				if (in_array($obj->id, $tokenperms)) {                    // Permission granted by user
					print '<!-- user has perm -->';
					if ($canedittoken) {
						print '<td class="center nowrap">';
						print '<a class="reposition addexpandedmodulesinparamlist" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&tokenid='.$tokenid.'&action=delrights&token='.newToken().'&rights='.$obj->id.'&confirm=yes">';
						//print img_edit_remove($langs->trans("Remove"));
						print img_picto($langs->trans("Remove"), 'switch_on');
						print '</a>';
						print '</td>';
					} else {
						print '<td class="center nowrap">';
						print img_picto($langs->trans("Active"), 'switch_on', '', 0, 0, 0, '', 'opacitymedium');
						print '</td>';
					}
					print '<td>';
					print '</td>';
				} else {
					print '<!-- permsgroupbyentitypluszero -->';
					if (in_array($obj->id, $permsgroupbyentitypluszero)) {    // Permission granted by group
						print '<td class="center nowrap">';
						print img_picto($langs->trans("Active"), 'switch_on', '', 0, 0, 0, '', 'opacitymedium');
						//print img_picto($langs->trans("Active"), 'tick');
						print '</td>';
						print '<td class="center nowrap">';
						print $form->textwithtooltip($langs->trans("Inherited"), $langs->trans("PermissionInheritedFromAGroup"));
						print '</td>';
					} else {
						// Do not own permission
						if ($canedittoken && in_array($obj->id, $allusersperms)) {
							print '<td class="center nowrap">';
							print '<a class="reposition addexpandedmodulesinparamlist" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&tokenid='.$tokenid.'&action=addrights&rights='.$obj->id.'&confirm=yes&token='.newToken().'">';
							//print img_edit_add($langs->trans("Add"));
							print img_picto($langs->trans("Add"), 'switch_off');
							print '</a>';
							print '</td>';
						} else {
							print '<td class="center nowrap">';
							print img_picto($langs->trans("Disabled"), 'switch_off', '', 0, 0, 0, '', 'opacitymedium');
							print '</td>';
						}
						print '<td>';
						print '</td>';
					}
				}

				// Description of permission (1 or 2 columns)
				if (!$user->admin) {
					print '<td colspan="2">';
				} else {
					print '<td>';
				}

				print $permlabel;
				$idtouse = $obj->id;
				if (in_array($idtouse, array(121, 122, 125, 126))) {    // Force message for the 3 permission on third parties
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
				// Special warning case for the permission "Allow to modify other users password"
				if ($obj->module == 'user' && $obj->perms == 'user' && $obj->subperms == 'password') {
					if ((!empty($object->admin) && !empty($objMod->rights_admin_allowed)) ||
						in_array($obj->id, $tokenperms) /* if edited user owns this permissions */ ||
						(in_array($obj->id, $permsgroupbyentitypluszero))) {
						print ' '.img_warning($langs->trans("AllowPasswordResetBySendingANewPassByEmail"));
					}
				}
				// Special warning case for the permission "Create/modify other users, groups and permissions"
				if ($obj->module == 'user' && $obj->perms == 'user' && ($obj->subperms == 'creer' || $obj->subperms == 'create')) {
					if ((!empty($object->admin) && !empty($objMod->rights_admin_allowed)) ||
						in_array($obj->id, $tokenperms) /* if edited user owns this permissions */ ||
						(in_array($obj->id, $permsgroupbyentitypluszero))) {
						print ' '.img_warning($langs->trans("AllowAnyPrivileges"));
					}
				}
				// Special case for reading bank account when you have permission to manage Chart of account
				if ($obj->module == 'banque' && $obj->perms == 'lire') {
					if (isModEnabled("accounting") && $object->hasRight('accounting', 'chartofaccount')) {
						print ' '.img_warning($langs->trans("WarningReadBankAlsoAllowedIfUserHasPermission"));
					}
				}

				print '</td>';

				// Permission id
				if ($user->admin) {
					print '<td class="right">';
					$htmltext = $langs->trans("ID").': '.$obj->id;
					$htmltext .= '<br>'.$langs->trans("Permission").': user->hasRight(\''.dol_escape_htmltag($obj->module).'\', \''.dol_escape_htmltag($obj->perms).'\''.($obj->subperms ? ', \''.dol_escape_htmltag($obj->subperms).'\'' : '').')';
					print $form->textwithpicto('', $htmltext, 1, 'help', 'inline-block marginrightonly');
					//print '<span class="opacitymedium">'.$obj->id.'</span>';
					print '</td>';
				}

				print '</tr>'."\n";

				$i++;
			}
		} else {
			dol_print_error($db);
		}
	} else {
		print '<tr class="oddeven"><td colspan="4"><span class="opacitymedium">'.$langs->trans("UserHasNoPermissions").'</span></td></tr>';
	}
	print '</table>';
	print '</div>';

	print '<script nonce="'.getNonce().'">';
	print '$(".tdforbreakperms:not(.alink)").on("click", function(){
		console.log("Click on tdforbreakperms");
		moduletohide = $(this).data("hide-perms");
		j = $(this).data("j");
		if ($("#idforbreakperms_"+moduletohide).val() == 1) {
			console.log("idforbreakperms_"+moduletohide+" has value hidden=1, so we show all lines");
			$(".trtohide_"+moduletohide).show();
			$(".permtoshow_"+moduletohide).hide();
			$(".permtohide_"+moduletohide).show();
			$(".folderperms_"+moduletohide).hide();
			$(".folderopenperms_"+moduletohide).show();
			$("#idforbreakperms_"+moduletohide).val("0");
		} else if (! $(this).hasClass("tdforbreakpermsifnotempty")) {
			console.log("idforbreakperms_"+moduletohide+" has value hidden=0, so we hide all lines");
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
	print "\n";

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
}

if (isModEnabled('api') && $action == 'create') {
	include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
	print dolJSToSetRandomPassword('api_key', 'generate_api_key', 1);
}

// End of page
llxFooter();
$db->close();
