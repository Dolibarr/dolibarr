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
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/partnership/class/partnership.class.php');
dol_include_once('/partnership/lib/partnership.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("companies","partnership@partnership", "other"));

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
}

$societe = new Societe($db);
if ($socid > 0) {
	$societe->fetch($socid);
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


if (empty($conf->partnership->enabled)) accessforbidden();
if (empty($permissiontoread)) accessforbidden();
if ($action == 'edit' && empty($permissiontoadd)) accessforbidden();

$partnershipid = $object->fetch(0, "", $socid);
if(empty($action) && empty($partnershipid)){
	$action = 'create';
}
if (($action == 'update' || $action == 'edit') && $object->status != $object::STATUS_DRAFT && !empty($user->socid)) accessforbidden();

if(empty($socid) && $object){
	$socid = $object->fk_soc;
}
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

	$backtopage = dol_buildpath('/partnership/partnership.php', 1).'?socid='.($socid > 0 ? $socid : '__ID__');

	$triggermodname = 'PARTNERSHIP_MODIFY'; // Name of trigger action code to execute when we modify record

	if ($action == 'add' && $permissiontoread) {
		$error = 0;

		$db->begin();

		$now = dol_now();

		if (!$error) {
			$old_start_date = $object->date_partnership_start;

			$object->fk_soc           			= $socid;
			$object->date_partnership_start   	= (!GETPOST('date_partnership_start')) ? '' : $date_start;
			$object->date_partnership_end     	= (!GETPOST('date_partnership_end')) ? '' : $date_end;
			$object->date_creation 				= $now;
			$object->fk_user_creat 				= $user->id;
			$object->entity 					= $conf->entity;

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}
		}

		if (!$error) {
			$result = $object->create($user);
			if ($result < 0) {
				$error++;
				if ($result == -4) {
					setEventMessages($langs->trans("ErrorRefAlreadyExists"), null, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}

		if ($error) {
			$db->rollback();
			$action = 'edit';
		} else {
			$db->commit();
		}
	} elseif ($action == 'update' && $permissiontoread) {
		$error = 0;

		$db->begin();

		$now = dol_now();

		if (!$error) {
			$object->oldcopy = clone $object;

			$old_start_date = $object->date_partnership_start;

			$object->date_partnership_start   	= (!GETPOST('date_partnership_start')) ? '' : $date_start;
			$object->date_partnership_end     	= (!GETPOST('date_partnership_end')) ? '' : $date_end;
			$object->fk_user_creat 				= $user->id;
			$object->fk_user_modif 				= $user->id;

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}
		}

		if (!$error) {
			$result = $object->update($user);
			if ($result < 0) {
				$error++;
				if ($result == -4) {
					setEventMessages($langs->trans("ErrorRefAlreadyExists"), null, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}

		if ($error) {
			$db->rollback();
			$action = 'edit';
		} else {
			$db->commit();
		}
	} elseif ($action == 'confirm_close' || $action == 'update_extras') {
		include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

		header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid);
		exit;
	}
	
	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// // Actions to send emails
	// $triggersendname = 'PARTNERSHIP_SENTBYMAIL';
	// $autocopy = 'MAIN_MAIL_AUTOCOPY_PARTNERSHIP_TO';
	// $trackid = 'partnership'.$object->id;
	// include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}



/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Partnership");
llxHeader('', $title);

$form = new Form($db);

if ($socid) {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$langs->load("companies");

	$societe = new Societe($db);
	$result = $societe->fetch($socid);

	if (!empty($conf->notification->enabled)) {
		$langs->load("mails");
	}
	$head = societe_prepare_head($societe);

	print dol_get_fiche_head($head, 'partnership', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($societe, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

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
		print '</td></tr>';
	}

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();

	$params = '';

	$newcardbutton .= dolGetButtonTitle($langs->trans("NewProject"), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/projet/card.php?action=create&socid='.$societe->id.'&amp;backtopage='.urlencode($backtopage), '', 1, $params);

	print '<br>';
	
} else {
	dol_print_error('', 'Parameter socid not defined');
}

$object->fields['fk_soc']['visible'] = 0;

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Partnership")), '', '');

	$backtopageforcancel = DOL_URL_ROOT.'/partnership/partnership.php?socid='.$socid;

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="'.$socid.'">';
	print '<input type="hidden" name="fk_soc" value="'.$socid.'">';

	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head(array(), '');

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Validate")).'">';
	print '&nbsp; ';
	// print '<input type="'.($backtopage ? "submit" : "button").'" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage ? '' : ' onclick="javascript:history.go(-1)"').'>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';
}

// Part to edit record
if (($partnershipid || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Partnership"), '', '');

	$backtopageforcancel = DOL_URL_ROOT.'/partnership/partnership.php?socid='.$socid;

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="socid" value="'.$socid.'">';
	print '<input type="hidden" name="fk_soc" value="'.$socid.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {


	print load_fiche_titre($langs->trans("PartnershipDedicatedToThisThirdParty", $langs->transnoentitiesnoconv("Partnership")), '', '');

	$res = $object->fetch_optionals();

	// $head = partnershipPrepareHead($object);
	// print dol_get_fiche_head($head, 'card', $langs->trans("Partnership"), -1, $object->picto);

	$linkback = '';
	dol_banner_tab($object, 'id', $linkback, 0, 'rowid', 'ref');

	$formconfirm = '';

	// Close confirmation
	if ($action == 'close') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClose'), $langs->trans('ConfirmCloseAsk', $object->ref), 'confirm_close', $formquestion, 'yes', 1);
	}
	// Reopon confirmation
	if ($action == 'reopen') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToReopon'), $langs->trans('ConfirmReoponAsk', $object->ref), 'confirm_reopen', $formquestion, 'yes', 1);
	}

	// Refuse confirmatio
	if ($action == 'refuse') {
		//Form to close proposal (signed or not)
		$formquestion = array(
			array('type' => 'text', 'name' => 'reason_decline_or_cancel', 'label' => $langs->trans("Note"), 'morecss' => 'reason_decline_or_cancel', 'value' => '')				// Field to complete private note (not replace)
		);

		// if (!empty($conf->notification->enabled)) {
		// 	require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
		// 	$notify = new Notify($db);
		// 	$formquestion = array_merge($formquestion, array(
		// 		array('type' => 'onecolumn', 'value' => $notify->confirmMessage('PROPAL_CLOSE_SIGNED', $object->socid, $object)),
		// 	));
		// }

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ReasonDecline'), $text, 'confirm_refuse', $formquestion, '', 1, 250);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/partnership/partnership_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	// Buttons for actions

	if ($action != 'presend') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {

			if ($object->status == $object::STATUS_DRAFT) {
				print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?socid='.$socid.'&action=edit', '', $permissiontoadd);
			}

			// Cancel
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_ACCEPTED) {
					print dolGetButtonAction($langs->trans('Cancel'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontoadd);
				}
			}
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// if ($action != 'presend') {
	// 	print '<div class="fichecenter"><div class="fichehalfleft">';
	// 	print '<a name="builddoc"></a>'; // ancre

	// 	$includedocgeneration = 0;

	// 	// Documents
	// 	if ($includedocgeneration) {
	// 		$objref = dol_sanitizeFileName($object->ref);
	// 		$relativepath = $objref.'/'.$objref.'.pdf';
	// 		$filedir = $conf->partnership->dir_output.'/'.$object->element.'/'.$objref;
	// 		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
	// 		$genallowed = $user->rights->partnership->read; // If you can read, you can build the PDF to read content
	// 		$delallowed = $user->rights->partnership->write; // If you can create/edit, you can remove a file on card
	// 		print $formfile->showdocuments('partnership:Partnership', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
	// 	}

	// 	// Show links to link elements
	// 	$linktoelem = $form->showLinkToObjectBlock($object, null, array('partnership'));
	// 	$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


	// 	print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	// 	$MAXEVENT = 10;

	// 	$morehtmlright = '<a href="'.dol_buildpath('/partnership/partnership_agenda.php', 1).'?id='.$object->id.'">';
	// 	$morehtmlright .= $langs->trans("SeeAll");
	// 	$morehtmlright .= '</a>';

	// 	// List of actions on element
	// 	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	// 	$formactions = new FormActions($db);
	// 	$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

	// 	print '</div></div></div>';
	// }

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'partnership';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->partnership->dir_output;
	$trackid = 'partnership'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
