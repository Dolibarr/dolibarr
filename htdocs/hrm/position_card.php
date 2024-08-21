<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021 Greg Rastklan <greg.rastklan@atm-consulting.fr>
 * Copyright (C) 2021 Jean-Pascal BOUDET <jean-pascal.boudet@atm-consulting.fr>
 * Copyright (C) 2021 Grégory BLEMAND <gregory.blemand@atm-consulting.fr>
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
 *    \file       htdocs/hrm/position_card.php
 *    \ingroup    hrm
 *    \brief      Page to create/edit/view job position
 */


// Load Dolibarr environment
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/class/position.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/class/job.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/lib/hrm_position.lib.php';
//dol_include_once('/hrm/position.php');

// Get Parameters
$action 	= GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$id 	= GETPOSTINT('id');

// Initialize a technical objects
$form = new Form($db);
$object = new Position($db);
$res = $object->fetch($id);
if ($res < 0) {
	dol_print_error($db, $object->error);
}

// Permissions
$permissiontoread = $user->hasRight('hrm', 'all', 'read');
$permissiontoadd = $user->hasRight('hrm', 'all', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('hrm', 'all', 'delete');
$permissiondellink = $user->hasRight('hrm', 'all', 'write'); // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->hrm->multidir_output[isset($object->entity) ? $object->entity : 1] . '/position';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
if (empty($conf->hrm->enabled)) {
	accessforbidden();
}
if (!$permissiontoread || ($action === 'create' && !$permissiontoadd)) {
	accessforbidden();
}

$langs->loadLangs(array("hrm", "other"));



// Get parameters
$id 	= GETPOSTINT('id');
$fk_job = GETPOSTINT('fk_job');

$ref 	= GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'positioncard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//	$lineid   = GETPOST('lineid', 'int');

// Initialize a technical objects
//$object = new Position($db);
//$res = $object->fetch($id);
/*if ($res < 0) {
	dol_print_error($db, &$object->error);
}*/

$extrafields = new ExtraFields($db);

$diroutputmassaction = $conf->hrm->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array('positioncard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_' . $key, 'alpha')) {
		$search[$key] = GETPOST('search_' . $key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/hrm/position_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($fk_job))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/hrm/position_card.php', 1) . '?id=' . ($id > 0 ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'HRM_POSITION_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT . '/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT . '/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOSTINT('projectid'));
	}

	// Actions to send emails
	$triggersendname = 'HRM_POSITION_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_POSITION_TO';
	$trackid = 'position' . $object->id;
	include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';
}


/*
 * View
 */

displayPositionCard($object);



/**
 * 		Show the card of a position
 *
 * 		@param	Position		 $object		  Position object
 * 		@return void
 */
function displayPositionCard(&$object)
{
	global $user, $langs, $db, $conf, $extrafields, $hookmanager, $action, $permissiontoadd, $permissiontodelete;

	$id = $object->id;
	$ref = $object->ref;

	/*
	 * View
	 *
	 * Put here all code to build page
	 */

	$form = new Form($db);
	$formfile = new FormFile($db);
	$formproject = new FormProjets($db);

	$backtopage = GETPOST('backtopage', 'alpha');
	$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

	$title = $langs->trans("Position");
	$help_url = '';
	llxHeader('', $title, $help_url);


	// Part to edit record
	if (($id || $ref) && $action == 'edit') {
		print load_fiche_titre($langs->trans("Position"), '', 'object_' . $object->picto);

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="' . $object->id . '">';

		if ($backtopage) {
			print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
		}
		if ($backtopageforcancel) {
			print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
		}

		print dol_get_fiche_head();

		print '<table class="border centpercent tableforfieldedit">' . "\n";

		// Common attributes
		include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

		// Other attributes
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

		print '</table>';

		print dol_get_fiche_end();

		print '<div class="center"><input type="submit" class="button button-save" name="save" value="' . $langs->trans("Save") . '">';
		print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="' . $langs->trans("Cancel") . '">';
		print '</div>';

		print '</form>';
	}


	// Part to show record
	if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
		$res = $object->fetch_optionals();


		$head = positionCardPrepareHead($object);
		print dol_get_fiche_head($head, 'position', $langs->trans("Workstation"), -1, $object->picto);

		$formconfirm = '';

		// Confirmation to delete
		if ($action == 'delete') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeletePosition'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
		}

		// Call Hook formConfirm
		$parameters = array('formConfirm' => $formconfirm/*, 'lineid' => $lineid*/);
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
		$linkback = '<a href="'.DOL_URL_ROOT.'/hrm/position_list.php">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		$u_position = new User(($db));
		$u_position->fetch($object->fk_user);
		$morehtmlref .= ($u_position->id > 0 ? $u_position->getNomUrl(1) : $langs->trans('Employee').' : ');
		$job = new Job($db);
		$job->fetch($object->fk_job);
		$morehtmlref .= '<br>'.$langs->trans('JobProfile').' : '.$job->getNomUrl(1);
		$morehtmlref .= '</div>';

		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'rowid', $morehtmlref);


		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">' . "\n";

		// Common attributes
		//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
		//unset($object->fields['fk_project']);				// Hide field already shown in banner
		//unset($object->fields['fk_soc']);					// Hide field already shown in banner
		$object->fields['fk_user']['visible']=0; // Already in banner
		$object->fields['fk_job']['visible']=0; // Already in banner
		include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

		// Other attributes. Fields from hook formObjectOptions and Extrafields.
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';
		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();

		/*
		 * Action bar
		 */
		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook


		print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit&token=' . newToken(), '', $permissiontoadd);

		// Delete (need delete permission, or if draft, just need create/modify permission)
		print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), '', $permissiontodelete);
	}
}

//if ($action != 'presend') {
//	$formfile = new FormFile($db);
//	print '<div class="fichecenter"><div class="fichehalfleft">';
//
//	if (empty($conf->global->SOCIETE_DISABLE_BUILDDOC)) {
//		print '<a name="builddoc"></a>'; // ancre
//
//		/*
//		 * Generated documents
//		 */
//		$filedir = $conf->societe->multidir_output[$object->entity].'/'.$object->id;
//		$urlsource = $_SERVER["PHP_SELF"]."?socid=".$object->id;
//		$genallowed = $user->hasRight('societe', 'lire');
//		$delallowed = $user->hasRight('societe', 'creer');
//
//		print $formfile->showdocuments('company', $object->id, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 0, 0, 0, 28, 0, 'entity='.$object->entity, 0, '', $object->default_lang);
//	}
//
//
//	print '</div><div class="fichehalfright">';
//
//	$MAXEVENT = 10;
//
//	$morehtmlright = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/societe/agenda.php?socid='.$object->id);
//
//	// List of actions on element
//	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
//	$formactions = new FormActions($db);
//	$somethingshown = $formactions->showactions($object, '', $object->id, 1, '', $MAXEVENT, '', $morehtmlright); // Show all action for thirdparty
//
//	print '</div></div>';
//}


print '</table>' . "\n";
print '</div>' . "\n";

print '</form>' . "\n";


if ($action !== 'edit' && $action !== 'create') {
	print '<div class="fichecenter"><div class="fichehalfleft">';

	// Show links to link elements
	$linktoelem = $form->showLinkToObjectBlock($object, null, array('position'));
	$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


	print '</div><div class="fichehalfright">';

	$MAXEVENT = 10;

	$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/hrm/position_agenda.php?id='.$object->id);

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, $object->element . '@' . $object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

	print '</div></div>';
}


// End of page
llxFooter();
$db->close();
