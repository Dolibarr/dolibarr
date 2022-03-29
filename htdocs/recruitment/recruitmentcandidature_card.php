<?php
/* Copyright (C) 2020 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       recruitmentcandidature_card.php
 *		\ingroup    recruitment
 *		\brief      Page to create/edit/view recruitmentcandidature
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB','1');					// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER','1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC','1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN','1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION','1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION','1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK','1');					// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL','1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK','1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU','1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML','1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX','1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN",'1');						// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK','1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT','auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE','aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN',1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP','none');					// Disable all Content Security Policies


// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentcandidature.class.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/lib/recruitment_recruitmentcandidature.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("recruitment", "other", "users"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'recruitmentcandidaturecard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object = new RecruitmentCandidature($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->recruitment->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('recruitmentcandidaturecard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.


$permissiontoread = $user->rights->recruitment->recruitmentjobposition->read;
$permissiontoadd = $user->rights->recruitment->recruitmentjobposition->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->recruitment->recruitmentjobposition->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->rights->recruitment->recruitmentjobposition->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->recruitment->recruitmentjobposition->write; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->recruitment->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->statut == $object::STATUS_DRAFT) ? 1 : 0);
//$result = restrictedArea($user, 'recruitment', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);

//if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error = 0;

	$backurlforlist = dol_buildpath('/recruitment/recruitmentcandidature_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/recruitment/recruitmentcandidature_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}
	$triggermodname = 'RECRUITMENTCANDIDATURE_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'classin' && $permissiontoadd)
	{
		$object->setProject(GETPOST('projectid', 'int'));
	}
	if ($action == 'confirm_decline' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->setStatut($object::STATUS_REFUSED, null, '', $triggermodname);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'confirm_makeofferordecline' && $permissiontoadd && !GETPOST('cancel', 'alpha')) {
		if (!(GETPOST('status', 'int') > 0)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CloseAs")), null, 'errors');
			$action = 'makeofferordecline';
		} else {
			// prevent browser refresh from closing proposal several times
			if ($object->status == $object::STATUS_VALIDATED)
			{
				$db->begin();

				if (GETPOST('status', 'int') == $object::STATUS_REFUSED) {
					$result = $object->setStatut($object::STATUS_REFUSED, null, '', $triggermodname);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				} else {
					$result = $object->setStatut($object::STATUS_CONTRACT_PROPOSED, null, '', $triggermodname);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}

				if (!$error)
				{
					$db->commit();
				} else {
					$db->rollback();
				}
			}
		}
	}

	if ($action == 'confirm_closeas' && $permissiontoadd && !GETPOST('cancel', 'alpha')) {
		if (!(GETPOST('status', 'int') > 0)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CloseAs")), null, 'errors');
			$action = 'makeofferordecline';
		} else {
			// prevent browser refresh from closing proposal several times
			if ($object->status == $object::STATUS_CONTRACT_PROPOSED)
			{
				$db->begin();

				if (GETPOST('status', 'int') == $object::STATUS_CONTRACT_REFUSED) {
					$result = $object->setStatut($object::STATUS_CONTRACT_REFUSED, null, '', $triggermodname);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				} else {
					$result = $object->setStatut($object::STATUS_CONTRACT_SIGNED, null, '', $triggermodname);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}

				if (!$error)
				{
					$db->commit();
				} else {
					$db->rollback();
				}
			}
		}
	}

	// Create user from a member
	if ($action == 'confirm_create_user' && $confirm == 'yes' && $user->rights->user->user->creer) {
		if ($result > 0) {
			$jobposition = new RecruitmentJobPosition($db);
			$jobposition->fetch($object->fk_recruitmentjobposition);

			// Creation user
			$nuser = new User($db);
			$nuser->login = GETPOST('login', 'alphanohtml');
			$nuser->fk_soc = 0;
			$nuser->employee = 1;
			$nuser->firstname = $object->firstname;
			$nuser->lastname = $object->lastname;
			$nuser->personal_mobile = $object->phone;
			$nuser->birth = $object->date_birth;
			$nuser->salary = $object->remuneration_proposed;
			$nuser->fk_user = $jobposition->fk_user_supervisor; // Supervisor

			$result = $nuser->create($user);

			if ($result < 0) {
				$langs->load("errors");
				setEventMessages($langs->trans($nuser->error), null, 'errors');
				$action = 'create_user';
			} else {
				setEventMessages($langs->trans("NewUserCreated", $nuser->login), null, 'mesgs');
				$action = '';
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create_user';
		}
	}

	// Actions to send emails
	$triggersendname = 'RECRUITMENTCANDIDATURE_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_RECRUITMENTCANDIDATURE_TO';
	$trackid = 'recruitmentcandidature'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}




/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("RecruitmentCandidature");
$help_url = '';
llxHeader('', $title, $help_url);

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';


// Part to create
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("RecruitmentCandidature")), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	print dol_get_fiche_head(array(), '');

	// Set some default values
	//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print '<input type="'.($backtopage ? "submit" : "button").'" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage ? '' : ' onclick="javascript:history.go(-1)"').'>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print load_fiche_titre($langs->trans("RecruitmentCandidature"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

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
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
	$res = $object->fetch_optionals();

	$head = recruitmentcandidaturePrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("RecruitmentCandidature"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteRecruitmentCandidature'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	if ($action == 'makeofferordecline')
	{
		$langs->load("propal");

		//Form to close proposal (signed or not)
		$formquestion = array(
			array('type' => 'select', 'name' => 'status', 'label' => '<span class="fieldrequired">'.$langs->trans("CloseAs").'</span>', 'values' => array($object::STATUS_CONTRACT_PROPOSED => $object->LibStatut($object::STATUS_CONTRACT_PROPOSED), $object::STATUS_REFUSED => $object->LibStatut($object::STATUS_REFUSED))),
			array('type' => 'text', 'name' => 'note_private', 'label' => $langs->trans("Note"), 'value' => '')				// Field to complete private note (not replace)
		);

		$text = '';

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('SetAcceptedRefused'), $text, 'confirm_makeofferordecline', $formquestion, '', 1, 250);
	}

	if ($action == 'closeas')
	{
		$langs->load("propal");

		//Form to close proposal (signed or not)
		$formquestion = array(
			array('type' => 'select', 'name' => 'status', 'label' => '<span class="fieldrequired">'.$langs->trans("CloseAs").'</span>', 'values' => array($object::STATUS_CONTRACT_SIGNED => $object->LibStatut($object::STATUS_CONTRACT_SIGNED), $object::STATUS_CONTRACT_REFUSED => $object->LibStatut($object::STATUS_CONTRACT_REFUSED))),
			array('type' => 'text', 'name' => 'note_private', 'label' => $langs->trans("Note"), 'value' => '')				// Field to complete private note (not replace)
		);

		$text = '';

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('SetAcceptedRefused'), $text, 'confirm_closeas', $formquestion, '', 1, 250);
	}

	// Confirm create user
	if ($action == 'create_user') {
		$login = (GETPOSTISSET('login') ? GETPOST('login', 'alphanohtml') : $object->login);
		if (empty($login)) {
			// Full firstname and name separated with a dot : firstname.name
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$login = dol_buildlogin($object->lastname, $object->firstname);
		}
		if (empty($login)) $login = strtolower(substr($object->firstname, 0, 4)).strtolower(substr($object->lastname, 0, 4));

		// Create a form array
		$formquestion = array(
			array('label' => $langs->trans("LoginToCreate"), 'type' => 'text', 'name' => 'login', 'value' => $login)
		);
		$text .= $langs->trans("ConfirmCreateLogin");
		print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("CreateDolibarrLogin"), $text, "confirm_create_user", $formquestion, 'yes');
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/recruitment/recruitmentcandidature_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	 // Ref customer
	 $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	 $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	 // Thirdparty
	 $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	 // Project
	 if (! empty($conf->projet->enabled))
	 {
	 $langs->load("projects");
	 $morehtmlref .= '<br>'.$langs->trans('Project') . ' ';
	 if ($permissiontoadd)
	 {
	 //if ($action != 'classify') $morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> ';
	 $morehtmlref .= ' : ';
	 if ($action == 'classify') {
	 //$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	 $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	 $morehtmlref .= '<input type="hidden" name="action" value="classin">';
	 $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
	 $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	 $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	 $morehtmlref .= '</form>';
	 } else {
	 $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	 }
	 } else {
	 if (! empty($object->fk_project)) {
	 $proj = new Project($db);
	 $proj->fetch($object->fk_project);
	 $morehtmlref .= ': '.$proj->getNomUrl();
	 } else {
	 $morehtmlref .= '';
	 }
	 }
	 }*/
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$keyforbreak = 'description'; // We change column just before this field
	unset($object->fields['email']); // Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Lines
	 */

	if (!empty($object->table_element_line))
	{
		// Show object lines
		$result = $object->getLinesArray();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '#addline' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline'))
		{
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		}

		if (!empty($object->lines))
		{
			$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);
		}

		// Form to add new line
		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines')
		{
			if ($action != 'editline')
			{
				// Add products/services form
				$object->formAddObjectLine(1, $mysoc, $soc);

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			}
		}

		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline'))
		{
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
	}


	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook))
		{
			// Send
			if (empty($user->socid)) {
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init&sendto='.urlencode($object->email).'#formmailbeforetitle">'.$langs->trans('SendMail').'</a>'."\n";
			}

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED)
			{
				if ($permissiontoadd)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes">'.$langs->trans("SetToDraft").'</a>';
				}
			}

			// Modify
			if ($permissiontoadd)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit">'.$langs->trans("Modify").'</a>'."\n";
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Modify').'</a>'."\n";
			}

			// Validate
			if ($object->status == $object::STATUS_DRAFT)
			{
				if ($permissiontoadd)
				{
					if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0))
					{
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes">'.$langs->trans("Validate").'</a>';
					} else {
						$langs->load("errors");
						print '<a class="butActionRefused" href="" title="'.$langs->trans("ErrorAddAtLeastOneLineFirst").'">'.$langs->trans("Validate").'</a>';
					}
				}
			}

			// Make offer - Refuse - Decline
			if ($object->status >= $object::STATUS_VALIDATED && $object->status < $object::STATUS_CONTRACT_PROPOSED)
			{
				if ($permissiontoadd)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=makeofferordecline">'.$langs->trans("MakeOffer").' / '.$langs->trans("Decline").'</a>';
				}
			}

			// Contract refused / accepted
			if ($object->status == $object::STATUS_CONTRACT_PROPOSED)
			{
				if ($permissiontoadd)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=closeas">'.$langs->trans("Accept").' / '.$langs->trans("Decline").'</a>';
				}
			}

			// Clone
			if ($permissiontoadd)
			{
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&socid='.$object->socid.'&action=clone&object=recruitmentcandidature">'.$langs->trans("ToClone").'</a>'."\n";
			}

			// Button to convert into a user
			if ($object->status == $object::STATUS_CONTRACT_SIGNED)
			{
				if ($user->rights->user->user->creer) {
					// TODO Check if a user already exists
					$useralreadyexists = 0;
					if (empty($useralreadyexists)) {
						print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=create_user">'.$langs->trans("CreateDolibarrLogin").'</a></div>';
					} else {
						print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">'.$langs->trans("CreateDolibarrLogin").'</a></div>';
					}
				} else {
					print '<div class="inline-block divButAction"><font class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("CreateDolibarrLogin")."</font></div>";
				}
			}

			// Cancel
			if ($permissiontoadd)
			{
				if ($object->status == $object::STATUS_VALIDATED)
				{
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=close">'.$langs->trans("Cancel").'</a>'."\n";
				}
				elseif ($object->status == $object::STATUS_REFUSED || $object->status == $object::STATUS_CANCELED || $object->status == $object::STATUS_CONTRACT_REFUSED)
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_reopen&confirm=yes&token='.newToken().'">'.$langs->trans("Re-Open").'</a>'."\n";
				}
			}

			// Delete (need delete permission, or if draft, just need create/modify permission)
			if ($permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd))
			{
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete&amp;token='.newToken().'">'.$langs->trans('Delete').'</a>'."\n";
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Delete').'</a>'."\n";
			}
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend')
	{
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->recruitment->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $user->rights->recruitment->recruitmentjobposition->read; // If you can read, you can build the PDF to read content
			$delallowed = $user->rights->recruitment->recruitmentjobposition->write; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('recruitment:RecruitmentCandidature', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('recruitmentcandidature'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		$MAXEVENT = 10;

		$morehtmlright = '<a href="'.dol_buildpath('/recruitment/recruitmentcandidature_agenda.php', 1).'?id='.$object->id.'">';
		$morehtmlright .= $langs->trans("SeeAll");
		$morehtmlright .= '</a>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element.'@recruitment', (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

		print '</div></div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) $action = 'presend';

	// Presend form
	$modelmail = 'recruitmentcandidature_send';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->recruitment->dir_output;
	$trackid = 'recruitmentcandidature'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
