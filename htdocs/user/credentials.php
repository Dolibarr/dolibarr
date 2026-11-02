<?php
/**
 * Copyright (C) 2025	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2025		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026       Frédéric France         <frederic.france@free.fr>
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
 *       \file       htdocs/user/credentials.php
 *       \brief      Tab of user credentials
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var ExtraFields $extrafields
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string 	$dolibarr_main_authentication
 * @var string	$dolibarr_api_count_always_enabled
 */
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
if (isModEnabled('ldap')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
}
if (isModEnabled('member')) {
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
}
if (isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}
if (isModEnabled('stock')) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
}

// Load translation files required by page
$langs->loadLangs(array('users', 'companies', 'ldap', 'admin', 'hrm', 'stocks', 'other'));

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$mode = GETPOST('mode', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$optioncss = GETPOST('optioncss', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'usercredentials'; // To manage different context of search
$backtopage = GETPOST('backtopage');
$backtopageforcancel = GETPOST('backtopageforcancel');

$group = GETPOSTINT("group", 3);

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


if (empty($id) && $action != 'add' && $action != 'create') {
	$id = $user->id;
}

$object = new User($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('usercard', 'globalcard'));

$error = 0;

if ($id > 0) {
	$res = $object->fetch($id, '', '', 1);
}

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = 'user';
$result = restrictedArea($user, 'user', $id, 'user', $feature2);

// Define value to know what current user can do on users. A test on logged user is done later to complete
$permissiontoadd = (!empty($user->admin) || $user->hasRight("user", "user", "write")) && (empty($user->socid) || $user->socid == $object->socid);
$permissiontoread = (!empty($user->admin) || $user->hasRight("user", "user", "read")) && (empty($user->socid) || $user->socid == $object->socid);
$permissiontoedit = (!empty($user->admin) || $user->hasRight("user", "user", "write")) && (empty($user->socid) || $user->socid == $object->socid);
$permissiontodisable = (!empty($user->admin) || $user->hasRight("user", "user", "delete")) && (empty($user->socid) || $user->socid == $object->socid);
$permissiontoreadgroup = $permissiontoread;
$permissiontoeditgroup = $permissiontoedit;
if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
	$permissiontoreadgroup = (!empty($user->admin) || $user->hasRight("user", "group_advance", "read")) && (empty($user->socid) || $user->socid == $object->socid);
	$permissiontoeditgroup = (!empty($user->admin) || $user->hasRight("user", "group_advance", "write")) && (empty($user->socid) || $user->socid == $object->socid);
}

$permissiontoclonesuperadmin = ($permissiontoadd && empty($user->entity));
$permissiontocloneadmin = ($permissiontoadd && !empty($user->admin));
$permissiontocloneuser = $permissiontoadd;
// Can clone only in master entity if transverse mode is used
if (getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $conf->entity > 1) {
	$permissiontoclonesuperadmin = false;
	$permissiontocloneadmin = false;
	$permissiontocloneuser = false;
}

if ($user->id != $id && !$permissiontoread) {
	accessforbidden();
}

$caneditpasswordandsee = false;
$caneditpasswordandsend = false;

// Define value to know what current user can do on properties of edited user
$permissiontoeditpasswordandsee = false;
$permissiontoeditpasswordandsend = false;
if ($id > 0) {
	// $user is the current logged user, $id is the user we want to edit
	$permissiontoedit = ((($user->id == $id) && $user->hasRight("user", "self", "write")) || (($user->id != $id) && $user->hasRight("user", "user", "write"))) && (empty($user->socid) || $user->socid == $object->socid);
	$permissiontoeditpasswordandsee = ((($user->id == $id) && $user->hasRight("user", "self", "password")) || (($user->id != $id) && $user->hasRight("user", "user", "password") && $user->admin)) && (empty($user->socid) || $user->socid == $object->socid);
	$permissiontoeditpasswordandsend = ((($user->id == $id) && $user->hasRight("user", "self", "password")) || (($user->id != $id) && $user->hasRight("user", "user", "password"))) && (empty($user->socid) || $user->socid == $object->socid);
}


/*
 * Actions
 */

$parameters = array('id' => $id, 'socid' => $socid, 'group' => $group, 'caneditgroup' => $permissiontoeditgroup);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/user/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/user/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	// Action to start a TOTP enrollment. A user enrolls only for themselves, so the secret is never known by anybody else.
	if ($action == 'addtotp' && $permissiontoeditpasswordandsee && $user->id == $object->id && getDolGlobalString('MAIN_SECURITY_ALLOW_TOTP')) {
		if (dolTotpGetEnrollment($db, $object->id) !== null) {
			setEventMessages($langs->trans("TOTPAlreadyEnrolled"), null, 'warnings');
			$action = '';
		} elseif (empty($_SESSION['dol_totp_enroll'][$object->id]) || $_SESSION['dol_totp_enroll'][$object->id]['expire'] < dol_now()) {
			// The candidate secret stays in session and reaches the database only once a valid code has confirmed it
			$_SESSION['dol_totp_enroll'][$object->id] = array('secret' => dolTotpNewSecret(), 'expire' => dol_now() + 600);
		}
	}

	// Action to confirm a TOTP enrollment with a first valid code, which proves the application is correctly set up.
	// The user is authenticated and sees the secret on the same page, so there is nothing to guess: the attempts are capped only to be safe.
	if ($action == 'confirmtotp' && $permissiontoeditpasswordandsee && $user->id == $object->id && getDolGlobalString('MAIN_SECURITY_ALLOW_TOTP')) {
		$enroll = empty($_SESSION['dol_totp_enroll'][$object->id]) ? array() : $_SESSION['dol_totp_enroll'][$object->id];
		if (empty($enroll['secret']) || $enroll['expire'] < dol_now()) {
			unset($_SESSION['dol_totp_enroll'][$object->id]);
			setEventMessages($langs->trans("TOTPEnrollmentExpired"), null, 'errors');
			$action = '';
		} else {
			$laststep = dolTotpCheckCode($enroll['secret'], GETPOST('totpcode', 'alphanohtml', 2));
			if ($laststep <= 0) {
				dol_syslog("Two-factor authentication enrollment of ".$object->login.": confirmation code refused", LOG_WARNING);
				$_SESSION['dol_totp_enroll'][$object->id]['tries'] = (empty($enroll['tries']) ? 0 : $enroll['tries']) + 1;
				if ($_SESSION['dol_totp_enroll'][$object->id]['tries'] >= 5) {	// A new secret is generated by the next enrollment
					unset($_SESSION['dol_totp_enroll'][$object->id]);
					setEventMessages($langs->trans("TOTPEnrollmentExpired"), null, 'errors');
					$action = '';
				} else {
					setEventMessages($langs->trans("TOTPBadCode"), null, 'errors');
					$action = 'addtotp';
				}
			} else {
				$result = dolTotpSaveEnrollment($db, $object->id, $enroll['secret'], $laststep, $object->entity);
				if ($result > 0) {
					unset($_SESSION['dol_totp_enroll'][$object->id]);
					dol_syslog("Two-factor authentication enabled for ".$object->login, LOG_NOTICE);
					setEventMessages($langs->trans("TOTPEnrolled"), null, 'mesgs');
					$action = '';
				} elseif ($result == -2) {
					unset($_SESSION['dol_totp_enroll'][$object->id]);
					setEventMessages($langs->trans("TOTPNoEncryptionKey"), null, 'errors');
					$action = '';
				} else {
					setEventMessages($db->lasterror(), null, 'errors');
					$action = 'addtotp';
				}
			}
		}
	}

	// Action to give up an enrollment in progress: the candidate secret is dropped, a new enrollment will show a new one
	if ($action == 'canceltotp' && $permissiontoeditpasswordandsee && $user->id == $object->id) {
		unset($_SESSION['dol_totp_enroll'][$object->id]);
		$action = '';
	}

	// Action to remove a TOTP enrollment. Not conditioned by MAIN_SECURITY_ALLOW_TOTP, so an admin can still remove one after the option has been turned off.
	if ($action == 'confirm_deletetotp' && $confirm == 'yes' && $permissiontoeditpasswordandsee) {
		if (dolTotpDeleteEnrollment($db, $object->id) > 0) {
			dol_syslog("Two-factor authentication disabled for ".$object->login." by ".$user->login, LOG_WARNING);
			setEventMessages($langs->trans("TOTPRemoved"), null, 'mesgs');
		} else {
			setEventMessages($db->lasterror(), null, 'errors');
		}
		$action = '';
	}
}


/*
 * View
 */

$form = new Form($db);

$person_name = !empty($object->firstname) ? $object->lastname.", ".$object->firstname : $object->lastname;
$title = $person_name." - ".$langs->trans('Credentials');
$help_url = '';

if ($action == 'addtotp') {	// This page shows the secret of an enrollment, it must not stay in any cache: same headers as top_httphead() with $forcenocache
	header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
}

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-credentials');

$param = '';


// Section TOTP

// Panel of an enrollment in progress. The secret is shown here and nowhere else, as a QR code to scan and as a key to type by hand.
if ($action == 'addtotp' && $user->id == $object->id && !empty($_SESSION['dol_totp_enroll'][$object->id]['secret'])) {
	$secrettoshow = $_SESSION['dol_totp_enroll'][$object->id]['secret'];

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="confirmtotp">';
	print '<input type="hidden" name="id" value="'.((int) $object->id).'">';
	print '<div class="info">';
	print '<div class="paddingbottom">'.$langs->trans("TOTPScanQrCode").'</div>';

	require_once TCPDF_PATH.'tcpdf_barcodes_2d.php';
	$qrobj = new TCPDF2DBarcode(dolTotpGetUri($object->login, $secrettoshow), 'QRCODE,M');
	$qrpng = $qrobj->getBarcodePngData(4, 4, array(0, 0, 0));
	if (!empty($qrpng)) {
		print '<div class="paddingbottom"><img src="data:image/png;base64,'.base64_encode($qrpng).'" alt=""></div>';
	}

	print '<div class="paddingbottom">'.$langs->trans("TOTPOrTypeKey").' <strong>'.dolPrintLabel(trim(chunk_split($secrettoshow, 4, ' '))).'</strong></div>';
	print '<div>'.$langs->trans("TOTPConfirmCode").' ';
	print '<input type="text" name="totpcode" class="width75" autocomplete="off" inputmode="numeric" maxlength="8" autofocus>';
	print ' <input type="submit" class="button small" value="'.$langs->trans("Validate").'">';
	print ' <a class="button button-cancel small" href="'.$_SERVER["PHP_SELF"].'?id='.((int) $object->id).'&action=canceltotp&token='.newToken().'">'.$langs->trans("Cancel").'</a>';
	print '</div>';
	print '</div><br>';
	print '</form>';
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

//$tmpurlforbutton = 'javascript:console.log("open add totp form");jQuery(".divsectiontotp").toggle(); void(0);';

// A user has at most one enrollment, and enrolls only for themselves
$totpenrollment = dolTotpGetEnrollment($db, $object->id);
$cancreatetotp = ($permissiontoeditpasswordandsee && $user->id == $object->id && getDolGlobalString('MAIN_SECURITY_ALLOW_TOTP') && $totpenrollment === null);

$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addtotp&token='.newToken(), '', $cancreatetotp ? 1 : 0);

//$listoftotps = $user->fetchAll($sortorder, $sortfield, 1000, 0, "(fk_user:=:".((int) $object->id).") AND (service:=:'dolibarr_totp')", true);
$nbtotalofrecords = $num = ($totpenrollment === null ? 0 : 1);

$massactionbutton = '';

print_barre_liste($langs->trans("TOTP"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $object->picto, 0, $newcardbutton, '', $limit, -1, 0, 1);

/*
print '<div class="hideobject divsectiontotp marginbottom">';
print '<input placeholder="'.dolPrintHTML("TOPTSecret").'" class="minwidth300 maxwidth400 widthcentpercentminusx" minlength="12" maxlength="128" type="text" id="api_key" name="api_key" value="'.GETPOST('api_key', 'alphanohtml').'" autocomplete="off">';
if (!empty($conf->use_javascript_ajax)) {
	print img_picto($langs->transnoentities('Generate'), 'refresh', 'id="generate_api_key" class="linkobject paddingleft"');
}
print '</div><br>';
*/

// Confirmation of the removal, which puts this user back to a password only login
if ($action == 'deletetotp' && $permissiontoeditpasswordandsee) {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.((int) $object->id), $langs->trans('TOTPDisable'), $langs->trans('TOTPConfirmDisable', $object->login), 'confirm_deletetotp', '', 0, 1);
}

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal liste">'."\n";

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
print_liste_field_titre('TOTPEnrolledOn');
print_liste_field_titre('TOTPLastUse');
print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
print '</tr>'."\n";

// The secret itself is never listed: it is shown only once, during the enrollment
if ($totpenrollment !== null) {
	print '<tr class="oddeven">';
	if (is_array($totpenrollment)) {
		print '<td>'.dol_print_date($totpenrollment['datec'], 'dayhour').'</td>';
		print '<td>'.($totpenrollment['lastaccess'] ? dol_print_date($totpenrollment['lastaccess'], 'dayhour') : '<span class="opacitymedium">'.$langs->trans("Never").'</span>').'</td>';
	} else {
		print '<td colspan="2"><span class="error">'.$langs->trans("TOTPUnreadableEnrollment").'</span></td>';	// The row exists but can't be read: only the removal is possible
	}
	print '<td class="center nowraponall">';
	if ($permissiontoeditpasswordandsee) {
		print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.((int) $object->id).'&action=deletetotp&token='.newToken().'">'.img_delete($langs->trans("TOTPDisable")).'</a>';
	}
	print '</td>';
	print '</tr>';
} else {
	print '<tr><td colspan="3"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

print '</table>'."\n";
print '</div>'."\n";

print '</form>';

// Add button to autosuggest a key
include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
print dolJSToSetRandomPassword('password', 'generate_password', 0);
if (isModEnabled('api')) {
	print dolJSToSetRandomPassword('api_key', 'generate_api_key', 1);
}




// Section Other

// ...



// End of page
llxFooter();
$db->close();
