<?php
/**
 * Copyright (C) 2025	Laurent Destailleur			<eldy@users.sourceforge.net>
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
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string 	$dolibarr_main_authentication
 * @var string	$dolibarr_api_count_always_enabled
 */
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
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
$search_secret_key = GETPOST('search_secret_key');

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
$extrafields = new ExtraFields($db);

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
	$permissiontoeditpasswordandsee = ((($user->id == $id) && $user->hasRight("user", "self", "password")) || (($user->id != $id) && $user->hasRight("user", "user", "password") && $user->admin))&& (empty($user->socid) || $user->socid == $object->socid);
	$permissiontoeditpasswordandsend = ((($user->id == $id) && $user->hasRight("user", "self", "password")) || (($user->id != $id) && $user->hasRight("user", "user", "password")))&& (empty($user->socid) || $user->socid == $object->socid);
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

	// Action to initialize data from a LDAP record
	if ($action == 'addtotp' && $permissiontoadd) {		// @phan-suppress-current-line PhanPluginEmptyStatementIf
		/*
		$result = $xxx->create();
		if ($result >= 0) {

		} else {
			setEventMessages($ldap->error, $ldap->errors, 'errors');
		}
		*/
	}
}


/*
 * View
 */

$form = new Form($db);

$person_name = !empty($object->firstname) ? $object->lastname.", ".$object->firstname : $object->lastname;
$title = $person_name." - ".$langs->trans('Credentials');
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-credentials');

$param = '';


// Section TOTP
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

$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addtotp&token='.newToken().'&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd ? 1 : 0);

//$listoftotps = $user->fetchAll($sortorder, $sortfield, 1000, 0, "(fk_user:=:".((int) $object->id).") AND (service:=:'dolibarr_totp')", true);
$listoftotps = array();
$sql = "SELECT rowid, token, state, restricted_ips, datec, tms, lastaccess FROM ".$db->prefix()."oauth_token";
$sql .= " WHERE fk_user = ".((int) $object->id)." AND service = 'dolibarr_totp'";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$listoftotps = array('id' => $obj->rowid);
	}
} else {
	dol_print_error($db);
}

$nbtotalofrecords = $num = count($listoftotps);

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

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal liste">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
print '<td class="liste_titre"><input type="text" name="search_secret_key" class="maxwidth50" value="'.$search_secret_key.'"></td>';
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>'."\n";

$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print_liste_field_titre('TOTPSecretKey', $_SERVER['PHP_SELF'], "u.rowid", $param, "", "", $sortfield, $sortorder);
$totalarray['nbfield']++;
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

foreach ($listoftotps as $totp) {
	// TODO
	print '<tr>';
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td></td>';
	}
	print '<td>';
	print $totp['id'];
	print '</td>';
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td></td>';
	}
	print '</tr>';
}

if (empty($listoftotps)) {
	print '<tr><td colspan="2"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

//print_fiche_titre($langs->trans("ApiKey"));

print '</table>'."\n";
print '</div>'."\n";

print '</form>';

// Add button to autosuggest a key
include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
print dolJSToSetRandomPassword('password', 'generate_password', 0);
if (isModEnabled('api')) {
	print dolJSToSetRandomPassword('api_key', 'generate_api_key', 1);
}



// Section API



















// End of page
llxFooter();
$db->close();
