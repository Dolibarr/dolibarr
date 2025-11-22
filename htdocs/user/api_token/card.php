<?php
/* Copyright (C) 2005-2017  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2015  Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2013	    Florian Henry               <florian.henry@open-concept.pro.com>
 * Copyright (C) 2018       Ferran Marcet               <fmarcet@2byte.es>
 * Copyright (C) 2024-2025  Frédéric France             <frederic.france@free.fr>
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
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';

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

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

// $user is current user, $id is id of edited user
$canreaduser = ($user->admin || ($user->id == $id));
$canedittoken = ($user->admin || (($user->id == $id) && $user->hasRight("user", "self", "write")));

if (!$canreaduser) {
	accessforbidden();
}


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

	if ($action == 'add' && $canedittoken) {
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

		$db->begin();

		if (!$error) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."oauth_token (service, token, state, fk_user, entity, datec)";
			$sql .= " VALUES ('dolibarr_rest_api', '".$db->escape(dolEncrypt($tokenstring, '', '', 'dolibarr'))."', 0, ".((int) $useridtoadd).", ".((int) $entity).", '".$db->idate(dol_now())."')";
			$resql = $db->query($sql);
			if (!$resql) {
				$error++;
			}

			// TODO Manage also ACL permission per token


			// TODO Manage also IP permission per token
		}

		if ($error) {
			dol_print_error($db);
			$db->rollback();
		} else {
			$insertedtokenid = $db->last_insert_id(MAIN_DB_PREFIX."oauth_token");
			$db->commit();

			header("Location: " . dolBuildUrl($_SERVER["PHP_SELF"], ['id' => $useridtoadd, 'tokenid' => $insertedtokenid]));
			exit;
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes' && $canedittoken) {
		// Remove token
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."oauth_token";
		$sql .= " WHERE rowid = ".((int) $tokenid);

		$resql = $db->query($sql);

		if ($resql) {
			header('Location: list.php?id='.((int) $object->id));
			exit;
		} else {
			dol_print_error($db);
		}
	}
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
		if (isModEnabled('multicompany') && !empty($object->admin) && empty($object->entity)) {
			$addadmin .= img_picto($langs->trans("SuperAdministratorDesc"), "redstar", 'class="paddingleft valignmiddle"');
		} elseif (!empty($object->admin)) {
			$addadmin .= img_picto($langs->trans("AdministratorDesc"), "star", 'class="paddingleft valignmiddle"');
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

	print 'TODO If no ACL given, show message to say permissions are the one of user. If ACL set, show ACL active (common to user permission)and ACL no more active (not own by user)';
}

if (isModEnabled('api') && $action == 'create') {
	include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
	print dolJSToSetRandomPassword('api_key', 'generate_api_key', 1);
}

// End of page
llxFooter();
$db->close();
