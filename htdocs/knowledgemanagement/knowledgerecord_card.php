<?php
/* Copyright (C) 2017-2021 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       knowledgerecord_card.php
 *		\ingroup    knowledgemanagement
 *		\brief      Page to create/edit/view knowledgerecord
 */

// Load Dolibarr environment
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/knowledgemanagement/class/knowledgerecord.class.php';
require_once DOL_DOCUMENT_ROOT.'/knowledgemanagement/lib/knowledgemanagement_knowledgerecord.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array("knowledgemanagement", "ticket", "other"));

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'knowledgerecordcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$lineid   = GETPOSTINT('lineid');

// Initialize a technical objects
$object = new KnowledgeRecord($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->knowledgemanagement->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('knowledgerecordcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.


$permissiontoread = $user->hasRight('knowledgemanagement', 'knowledgerecord', 'read');
$permissiontovalidate = $user->hasRight('knowledgemanagement', 'knowledgerecord', 'write');
$permissiontoadd = $user->hasRight('knowledgemanagement', 'knowledgerecord', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('knowledgemanagement', 'knowledgerecord', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->hasRight('knowledgemanagement', 'knowledgerecord', 'write'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('knowledgemanagement', 'knowledgerecord', 'write'); // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->knowledgemanagement->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
restrictedArea($user, $object->module, $object->id, $object->table_element, $object->element, '', 'rowid', $isdraft);
//if (empty($conf->knowledgemanagement->enabled)) accessforbidden();
//if (empty($permissiontoread)) accessforbidden();


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

	$backurlforlist = DOL_URL_ROOT.'/knowledgemanagement/knowledgerecord_list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/knowledgemanagement/knowledgerecord_card.php?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'KNOWLEDGERECORD_MODIFY'; // Name of trigger action code to execute when we modify record

	// Update / add for lang
	if (($action == 'update' || $action == 'add') && !empty($permissiontoadd)) {
		$object->lang = (GETPOSTISSET('langkm') ? GETPOST('langkm', 'aZ09') : $object->lang);
	}

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

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOSTINT('projectid'));
	}

	// Actions to send emails
	$triggersendname = 'KNOWLEDGEMANAGEMENT_KNOWLEDGERECORD_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_KNOWLEDGERECORD_TO';
	$trackid = 'knowledgerecord'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}
if ($action == 'confirm_validate') {
	$action = 'edit';
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);
$formadmin = new FormAdmin($db);

$title = $langs->trans("KnowledgeRecord");
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-knowledgemanagement page-card');

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewKnowledgeRecord"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if (!empty($backtopage)) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if (!empty($backtopageforcancel)) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head(array(), '', '', -3);

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	$object->fields['answer']['enabled'] = 0;
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';
	$object->fields['answer']['enabled'] = 1;

	if (isModEnabled('category')) {
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_KNOWLEDGEMANAGEMENT, '', 'parent', 64, 0, 3);

		if (count($cate_arbo)) {
			// Categories
			print '<tr><td>'.$langs->trans("Categories").'</td><td colspan="3">';
			print img_picto('', 'category').$form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
			print "</td></tr>";
		}
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	// Add field answer
	print '<br>';
	print $langs->trans($object->fields['answer']['label']).'<br>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('answer', $object->answer, '', 200, 'dolibarr_notes', 'In', true, true, true, ROWS_9, '100%');
	$out = $doleditor->Create(1);
	print $out;

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel('Create');

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("KnowledgeRecord"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if (!empty($backtopage)) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if (!empty($backtopageforcancel)) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head(array(), '', '', -3);

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	$object->fields['answer']['enabled'] = 0;
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';
	$object->fields['answer']['enabled'] = 1;

	if (isModEnabled('category')) {
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_KNOWLEDGEMANAGEMENT, '', 'parent', 64, 0, 3);

		if (count($cate_arbo)) {
			// Categories
			print '<tr><td>'.$langs->trans("Categories").'</td><td colspan="3">';
			$c = new Categorie($db);
			$cats = $c->containing($object->id, Categorie::TYPE_KNOWLEDGEMANAGEMENT);
			$arrayselected = array();
			if (is_array($cats)) {
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
			}
			print $form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
			print "</td></tr>";
		}
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	// Add field answer
	print '<br>';
	print $langs->trans($object->fields['answer']['label']).'<br>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('answer', $object->answer, '', 200, 'dolibarr_notes', 'In', true, true, true, ROWS_9, '100%');
	$out = $doleditor->Create(1);
	print $out;

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = knowledgerecordPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("KnowledgeRecord"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteKnowledgeRecord'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
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

	// Confirmation of action xxxx (You can use it for xxx = 'close', xxx = 'reopen', ...)
	if ($action == 'close') {
		$text = $langs->trans('ConfirmCloseKM', $object->ref);
		/*if (isModEnabled('notification'))
		 {
		 require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
		 $notify = new Notify($db);
		 $text .= '<br>';
		 $text .= $notify->confirmMessage('MYOBJECT_CLOSE', $object->socid, $object);
		 }*/

		$formquestion = array();
		/*
		 $forcecombo=0;
		 if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
		 $formquestion = array(
		 // 'text' => $langs->trans("ConfirmClone"),
		 // array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
		 // array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
		 // array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
		 );
		 */
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('SetObsolete'), $text, 'confirm_close', $formquestion, 0, 1, 220);
	}

	// Confirmation of action xxxx (You can use it for xxx = 'close', xxx = 'reopen', ...)
	if ($action == 'reopen') {
		$text = $langs->trans('ConfirmReopenKM', $object->ref);
		/*if (isModEnabled('notification'))
		 {
		 require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
		 $notify = new Notify($db);
		 $text .= '<br>';
		 $text .= $notify->confirmMessage('MYOBJECT_CLOSE', $object->socid, $object);
		 }*/

		$formquestion = array();
		/*
		 $forcecombo=0;
		 if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
		 $formquestion = array(
		 // 'text' => $langs->trans("ConfirmClone"),
		 // array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
		 // array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
		 // array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
		 );
		 */
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Re-Open'), $text, 'confirm_reopen', $formquestion, 0, 1, 220);
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
	$linkback = '<a href="'.DOL_URL_ROOT.'/knowledgemanagement/knowledgerecord_list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	 // Ref customer
	 $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	 $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	 // Thirdparty
	 $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	 // Project
	 if (isModEnabled('project')) {
	 $langs->load("projects");
	 $morehtmlref .= '<br>'.$langs->trans('Project') . ' ';
	 if ($permissiontoadd) {
	 //if ($action != 'classify') $morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> ';
	 $morehtmlref .= ' : ';
	 if ($action == 'classify') {
	 //$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 0, 1, '', 'maxwidth300');
	 $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	 $morehtmlref .= '<input type="hidden" name="action" value="classin">';
	 $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
	 $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	 $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	 $morehtmlref .= '</form>';
	 } else {
	 $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1, '', 'maxwidth300');
	 }
	 } else {
	 if (!empty($object->fk_project)) {
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
	$keyforbreak='fk_c_ticket_category';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	$object->fields['answer']['enabled'] = 0;
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';
	$object->fields['answer']['enabled'] = 1;

	// Categories
	if (isModEnabled('category')) {
		print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($object->id, Categorie::TYPE_KNOWLEDGEMANAGEMENT, 1);
		print "</td></tr>";
	}

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	// Add field answer
	print '<br>';
	print $langs->trans($object->fields['answer']['label']).'<br>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('answer', $object->answer, '', 200, 'dolibarr_notes', 'In', true, true, true, ROWS_9, '100%', 1);
	$out = $doleditor->Create(1);
	print $out;

	print dol_get_fiche_end();


	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Send
			if (empty($user->socid)) {
				//print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle');
			}

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED) {
				print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
			}
			if (($object->status == $object::STATUS_DRAFT || $object->status == $object::STATUS_VALIDATED) && $permissiontovalidate) {
				print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);
			}
			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				if ((empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) && $permissiontovalidate) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&token='.newToken().'&confirm=yes', '', $permissiontoadd);
				} else {
					$langs->load("errors");
					//print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes', '', 0);
					print '<a class="butActionRefused" href="" title="'.$langs->trans("ErrorAddAtLeastOneLineFirst").'">'.$langs->trans("Reply").'</a>';
				}
			}

			// Clone
			print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=clone&token='.newToken().'&object=scrumsprint', '', $permissiontoadd);

			/*
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_ENABLED) {
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=disable&token='.newToken().'">'.$langs->trans("Disable").'</a>'."\n";
				} else {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=enable&token='.newToken().'">'.$langs->trans("Enable").'</a>'."\n";
				}
			}
			*/
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED) {
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=close&token='.newToken().'">'.$langs->trans("SetObsolete").'</a>'."\n";
				} else {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=reopen&token='.newToken().'">'.$langs->trans("Re-Open").'</a>'."\n";
				}
			}

			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 0;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->knowledgemanagement->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $user->hasRight('knowledgemanagement', 'knowledgerecord', 'read'); // If you can read, you can build the PDF to read content
			$delallowed = $user->hasRight('knowledgemanagement', 'knowledgerecord', 'write'); // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('knowledgemanagement:KnowledgeRecord', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('knowledgerecord'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/knowledgemanagement/knowledgerecord_agenda.php?id='.$object->id);

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'knowledgerecord';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->knowledgemanagement->dir_output;
	$trackid = 'knowledgerecord'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
