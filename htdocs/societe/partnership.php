<?php
/* Copyright (C) 2017 Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2021 NextGestion 			<contact@nextgestion.com>
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
 *   	\file       partnership_card.php
 *		\ingroup    partnership
 *		\brief      Page to create/edit/view partnership
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/partnership/class/partnership.class.php';
require_once DOL_DOCUMENT_ROOT.'/partnership/lib/partnership.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies","partnership", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'partnershipcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Security check
$socid = GETPOST('socid', 'int');
if (!empty($user->socid)) {
	$socid = $user->socid;
	$id = $socid;
}

$object = new Societe($db);
if ($id > 0) {
	$object->fetch($id);
}

// Initialize technical objects
$object 		= new Partnership($db);
$extrafields 	= new ExtraFields($db);
$diroutputmassaction = $conf->partnership->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('partnershipthirdparty', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();

foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$permissiontoread 		= $user->rights->partnership->read;
$permissiontoadd 		= $user->rights->partnership->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete 	= $user->rights->partnership->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote 		= $user->rights->partnership->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink 		= $user->rights->partnership->write; // Used by the include of actions_dellink.inc.php
$usercanclose 			= $user->rights->partnership->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$upload_dir 			= $conf->partnership->multidir_output[isset($object->entity) ? $object->entity : 1];


if ($conf->global->PARTNERSHIP_IS_MANAGED_FOR != 'thirdparty') accessforbidden();
if (empty($conf->partnership->enabled)) accessforbidden();
if (empty($permissiontoread)) accessforbidden();
if ($action == 'edit' && empty($permissiontoadd)) accessforbidden();

if (($action == 'update' || $action == 'edit') && $object->status != $object::STATUS_DRAFT && !empty($user->socid)) {
	accessforbidden();
}


// Security check
$result = restrictedArea($user, 'societe', $id, '&societe', '', 'fk_soc', 'rowid', 0);


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$date_start = dol_mktime(0, 0, 0, GETPOST('date_partnership_startmonth', 'int'), GETPOST('date_partnership_startday', 'int'), GETPOST('date_partnership_startyear', 'int'));
$date_end = dol_mktime(0, 0, 0, GETPOST('date_partnership_endmonth', 'int'), GETPOST('date_partnership_endday', 'int'), GETPOST('date_partnership_endyear', 'int'));

if (empty($reshook)) {
	$error = 0;

	$backtopage = dol_buildpath('/partnership/partnership.php', 1).'?id='.($id > 0 ? $id : '__ID__');

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';
}

$object->fields['fk_soc']['visible'] = 0;
if ($object->id > 0 && $object->status == $object::STATUS_REFUSED && empty($action)) $object->fields['reason_decline_or_cancel']['visible'] = 1;
$object->fields['note_public']['visible'] = 1;


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$title = $langs->trans("Partnership");
llxHeader('', $title);

$form = new Form($db);

if ($id > 0) {
	$langs->load("companies");

	$object = new Societe($db);
	$result = $object->fetch($id);

	if (!empty($conf->notification->enabled)) {
		$langs->load("mails");
	}
	$head = societe_prepare_head($object);

	print dol_get_fiche_head($head, 'partnership', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	if (!empty($conf->global->SOCIETE_USEPREFIX)) {  // Old not used prefix field
		print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$societe->prefix_comm.'</td></tr>';
	}

	if ($societe->client) {
		print '<tr><td class="titlefield">';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print showValueWithClipboardCPButton(dol_escape_htmltag($societe->code_client));
		$tmpcheck = $societe->check_codeclient();
		if ($tmpcheck != 0 && $tmpcheck != -5) {
			print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		}
		print '</td></tr>';
	}

	if ($societe->fournisseur) {
		print '<tr><td class="titlefield">';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print showValueWithClipboardCPButton(dol_escape_htmltag($societe->code_fournisseur));
		$tmpcheck = $societe->check_codefournisseur();
		if ($tmpcheck != 0 && $tmpcheck != -5) {
			print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		}
		print '</td>';
		print '</tr>';
	}

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();
} else {
	dol_print_error('', 'Parameter id not defined');
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	// Buttons for actions

	if ($action != 'presend') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Show
			if ($permissiontoadd) {
				print dolGetButtonAction($langs->trans('AddPartnership'), '', 'default', DOL_URL_ROOT.'/partnership/partnership_card.php?action=create&fk_soc='.$object->id.'&backtopage='.urlencode(DOL_URL_ROOT.'/societe/partnership.php?id='.$object->id), '', $permissiontoadd);
			}
		}
		print '</div>'."\n";
	}


	//$morehtmlright = 'partnership/partnership_card.php?action=create&backtopage=%2Fdolibarr%2Fhtdocs%2Fpartnership%2Fpartnership_list.php';
	$morehtmlright = '';

	print load_fiche_titre($langs->trans("PartnershipDedicatedToThisThirdParty", $langs->transnoentitiesnoconv("Partnership")), $morehtmlright, '');

	$socid = $object->id;


	// TODO Replace this card with the list of all partnerships.

	$object = new Partnership($db);
	$partnershipid = $object->fetch(0, '', 0, $socid);

	if ($partnershipid > 0) {
		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">'."\n";

		// Common attributes
		//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
		//unset($object->fields['fk_project']);				// Hide field already shown in banner
		//unset($object->fields['fk_member']);					// Hide field already shown in banner
		include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

		print '</table>';
		print '</div>';
	}
}

// End of page
llxFooter();
$db->close();
