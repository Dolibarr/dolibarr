<?php
/* Copyright (C) 2026		Guenter Lukas			<gl@gl.co.at>
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
 *       \file       htdocs/user/passkeys.php
 *       \brief      Page to manage the passkeys (WebAuthn credentials) used to log in without password
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/passkey.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/userpasskey.class.php';

// Load translation files required by page
$langs->loadLangs(array('users', 'admin', 'errors'));

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$passkeyid = GETPOSTINT('passkeyid');

if (empty($id)) {
	$id = $user->id;
}

$object = new User($db);
if ($object->fetch($id, '', '', 1) <= 0) {
	accessforbidden();
}

// Security check
$result = restrictedArea($user, 'user', $id, 'user', 'user');

// Same permissions than to edit the password of the user. A passkey can be added by the user himself only:
// the private key is created on his own device. A user that must use a passkey can always manage his own passkeys.
$passkeyrequired = passkeyIsRequiredForUser($object);
$permissiontoeditpasswordandsee = ((($user->id == $id) && ($user->hasRight("user", "self", "password") || $passkeyrequired)) || (($user->id != $id) && $user->hasRight("user", "user", "password") && $user->admin)) && (empty($user->socid) || $user->socid == $object->socid);
$permissiontoadd = ($user->id == $id && $permissiontoeditpasswordandsee && passkeyIsEnabled());
// Only an administrator can decide if a user must use a passkey
$permissiontosetrequired = (!empty($user->admin) && empty($user->socid) && passkeyIsEnabled());

if (!$permissiontoeditpasswordandsee) {
	accessforbidden();
}

$passkeystatic = new UserPasskey($db);


/*
 * Actions
 */

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'confirm_delete' && $confirm == 'yes' && $permissiontoeditpasswordandsee) {
		$passkey = new UserPasskey($db);
		if ($passkey->fetch($passkeyid) > 0 && $passkey->fk_user == $object->id) {
			if ($passkey->delete($user) > 0) {
				dol_syslog("Passkey id=".$passkey->id." of user id=".$object->id." deleted by user id=".$user->id, LOG_NOTICE);
				setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
			} else {
				setEventMessages($passkey->error, $passkey->errors, 'errors');
			}
		}
		$action = '';
	}

	if ($action == 'setrequired' && $permissiontosetrequired) {
		$value = GETPOST('passkey_required', 'aZ09');
		$result = $object->setPasskeyRequired($user, ($value === '' ? null : (int) $value));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} elseif ($result > 0) {
			setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
		}
		$passkeyrequired = passkeyIsRequiredForUser($object);
		$action = '';
	}

	if ($action == 'setlabel' && $user->id == $object->id && $permissiontoeditpasswordandsee) {
		$passkey = new UserPasskey($db);
		if ($passkey->fetch($passkeyid) > 0 && $passkey->fk_user == $object->id) {
			if ($passkey->setLabel(dol_trunc(GETPOST('label', 'alphanohtml'), 250, 'right', 'UTF-8', 1)) < 0) {
				setEventMessages($passkey->error, $passkey->errors, 'errors');
			}
		}
		$action = '';
	}
}


/*
 * View
 */

$form = new Form($db);

$title = $object->getFullName($langs)." - ".$langs->trans('Passkeys');

$arrayofjs = array();
if ($permissiontoadd) {
	$arrayofjs[] = '/core/js/passkey.js';
}

llxHeader('', $title, '', '', 0, 0, $arrayofjs, '', '', 'mod-user page-passkeys');

if ($action == 'delete') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&passkeyid='.$passkeyid, $langs->trans("DeletePasskey"), $langs->trans("ConfirmDeletePasskey"), 'confirm_delete', '', 0, 1);
}

$morehtmlright = '<a class="button smallpaddingimp" href="'.DOL_URL_ROOT.'/user/card.php?id='.$object->id.'">'.img_picto('', 'user', 'class="pictofixedwidth"').$langs->trans("BackToUserCard").'</a>';
print load_fiche_titre($langs->trans("Passkeys").' - '.dolPrintHTML($object->getFullName($langs)), $morehtmlright, 'fa-fingerprint');

print '<span class="opacitymedium">'.$langs->trans("PasskeysDesc").'</span><br><br>';

if (!passkeyIsEnabled()) {
	print info_admin($langs->trans("PasskeyModeNotEnabled", 'passkey', '$dolibarr_main_authentication'), 0, 0, 'warning', '');
} else {
	$setuperror = passkeyCheckSetup();
	if ($setuperror) {
		print info_admin($setuperror, 0, 0, 'error', '');
	}
}

// Passkey required for this user
$nbofpasskeys = $passkeystatic->countForUser($object->id);
if ($passkeyrequired && $nbofpasskeys <= 0) {
	print info_admin($langs->trans($user->id == $object->id ? "PasskeyRequiredCreateOne" : "PasskeyRequiredNoPasskeyYet"), 0, 0, 'warning', '');
}
if ($permissiontosetrequired) {
	$globalrequired = getDolGlobalInt('MAIN_AUTHENTICATION_PASSKEY_REQUIRED');
	$arrayofglobalvalues = array(0 => $langs->trans("PasskeyRequiredNobody"), 1 => $langs->trans("PasskeyRequiredAdmins"), 2 => $langs->trans("PasskeyRequiredAllUsers"));
	$arrayofvalues = array(
		'' => $langs->trans("PasskeyRequiredDefault", $arrayofglobalvalues[$globalrequired] ?? ''),
		'1' => $langs->trans("PasskeyRequiredYes"),
		'0' => $langs->trans("PasskeyRequiredNo"),
	);
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" class="marginbottomonly">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setrequired">';
	print $form->textwithpicto($langs->trans("PasskeyRequiredForThisUser"), $langs->trans("PasskeyRequiredHelp")).' ';
	print $form->selectarray('passkey_required', $arrayofvalues, ($object->passkey_required === null ? '' : (string) $object->passkey_required), 0, 0, 0, '', 0, 0, 0, '', 'minwidth200');
	print ' <input type="submit" class="button smallpaddingimp" value="'.dolPrintHTMLForAttribute($langs->trans("Save")).'">';
	print '</form>';
}

// Form to add a passkey
if ($permissiontoadd) {
	print '<div class="marginbottomonly">';
	print '<input type="text" id="passkeylabel" name="label" class="minwidth300" maxlength="250" placeholder="'.dolPrintHTMLForAttribute($langs->trans("PasskeyLabelPlaceholder")).'"> ';
	print '<button type="button" class="button smallpaddingimp" id="passkeyadd"';
	print ' data-url="'.dol_escape_htmltag(DOL_URL_ROOT.'/core/ajax/passkeyregister.php').'"';
	print ' data-msg-error="'.dolPrintHTMLForAttribute($langs->transnoentitiesnoconv("PasskeyCreationFailed")).'"';
	print ' data-msg-alreadyregistered="'.dolPrintHTMLForAttribute($langs->transnoentitiesnoconv("PasskeyErrorAlreadyRegistered")).'"';
	print ' data-msg-notallowed="'.dolPrintHTMLForAttribute($langs->transnoentitiesnoconv("PasskeyErrorNotAllowed")).'"';
	print ' data-msg-insecure="'.dolPrintHTMLForAttribute($langs->transnoentitiesnoconv("PasskeyErrorInsecure")).'"';
	print ' data-msg-notsupported="'.dolPrintHTMLForAttribute($langs->transnoentitiesnoconv("PasskeyNotSupportedByBrowser")).'"';
	print ' data-msg-baddomain="'.dolPrintHTMLForAttribute($langs->transnoentitiesnoconv("PasskeyErrorBadOrigin", passkeyGetOrigin())).'"';
	print '>';
	print img_picto('', 'fa-plus-circle', 'class="pictofixedwidth"').$langs->trans("AddPasskey");
	print '</button>';
	print '</div>';
}

// List of passkeys
$listofpasskeys = $passkeystatic->fetchAllForUser($object->id);
if (!is_array($listofpasskeys)) {
	dol_print_error($db, $passkeystatic->error);
	$listofpasskeys = array();
}

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste">'."\n";
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Label").'</td>';
print '<td class="center">'.$langs->trans("DateCreation").'</td>';
print '<td class="center">'.$langs->trans("PasskeyLastUse").'</td>';
print '<td class="center">'.$form->textwithpicto($langs->trans("PasskeySynced"), $langs->trans("PasskeySyncedHelp")).'</td>';
print '<td></td>';
print '</tr>'."\n";

foreach ($listofpasskeys as $passkey) {
	print '<tr class="oddeven">';

	print '<td class="tdoverflowmax300">';
	if ($action == 'editlabel' && $passkeyid == $passkey->id && $user->id == $object->id) {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="setlabel">';
		print '<input type="hidden" name="passkeyid" value="'.$passkey->id.'">';
		print '<input type="text" name="label" class="minwidth200" maxlength="250" value="'.dolPrintHTMLForAttribute($passkey->label).'" autofocus> ';
		print '<input type="submit" class="button smallpaddingimp" value="'.dolPrintHTMLForAttribute($langs->trans("Save")).'">';
		print '</form>';
	} else {
		print img_picto('', 'fa-fingerprint', 'class="pictofixedwidth"');
		print dolPrintHTML($passkey->label ? $passkey->label : $langs->trans("Passkey").' '.$passkey->id);
		if ($user->id == $object->id) {
			print ' <a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editlabel&passkeyid='.$passkey->id.'&token='.newToken().'">'.img_edit($langs->trans("Modify"), 0, 'class="paddingleft"').'</a>';
		}
	}
	print '</td>';

	print '<td class="center nowraponall">'.dol_print_date($passkey->date_creation, 'dayhour', 'tzuserrel').'</td>';

	print '<td class="center nowraponall">';
	if ($passkey->date_last_use) {
		print dol_print_date($passkey->date_last_use, 'dayhour', 'tzuserrel');
	}
	print '</td>';

	print '<td class="center">'.yn($passkey->backup_state).'</td>';

	print '<td class="right nowraponall">';
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&passkeyid='.$passkey->id.'&token='.newToken().'">'.img_delete().'</a>';
	print '</td>';

	print '</tr>'."\n";
}

if (empty($listofpasskeys)) {
	print '<tr><td colspan="5"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

print '</table>'."\n";
print '</div>'."\n";

// End of page
llxFooter();
$db->close();
