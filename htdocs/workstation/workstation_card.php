<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       workstation_card.php
 *		\ingroup    workstation
 *		\brief      Page to create/edit/view workstation
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/workstation/class/workstation.class.php';
require_once DOL_DOCUMENT_ROOT.'/workstation/lib/workstation_workstation.lib.php';
require_once DOL_DOCUMENT_ROOT.'/workstation/class/workstationusergroup.class.php';

// Load translation files required by the page
$langs->loadLangs(array("workstation", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'workstationcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$groups	= GETPOST('groups', 'array:int');
$resources	= GETPOST('resources', 'array:int');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object = new Workstation($db);
//$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->workstation->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('workstationcard', 'globalcard')); // Note that conf->hooks_modules contains array

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

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$permissiontoread = $user->rights->workstation->workstation->read;
$permissiontoadd = $user->rights->workstation->workstation->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->workstation->workstation->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->rights->workstation->workstation->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->workstation->workstation->write; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->workstation->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check
$isdraft = 0;
restrictedArea($user, $object->element, $object->id, $object->table_element, 'workstation', 'fk_soc', 'rowid', $isdraft);


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

	$backurlforlist = dol_buildpath('/workstation/workstation_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/workstation/workstation_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'WORKSTATION_WORKSTATION_MODIFY'; // Name of trigger action code to execute when we modify record

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

	if ($action == 'confirm_enable' && $confirm == "yes" && $permissiontoadd) {
		if (!empty($object->id)) {
			$object->setStatus(1);
		}
	} elseif ($action == 'confirm_disable' && $confirm == "yes" && $permissiontoadd) {
		if (!empty($object->id)) {
			$object->setStatus(0);
		}
	}
}




/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formresource = new FormResource($db);

$title = $langs->trans("Workstation");
$help_url = 'EN:Module_Workstation';

llxHeader('', $title, $help_url);

// jquery code
?>
	<script type="text/javascript">

		jQuery(document).ready(function() {
			jQuery("#type").change(function() {
				if($(this).val() === 'MACHINE') {
					$('#usergroups').hide();
					$('#nb_operators_required').parent('td').parent('tr').hide();
					$('#wsresources').show();
				} else if($(this).val() === 'HUMAN') {
					$('#wsresources').hide();
					$('#nb_operators_required').parent('td').parent('tr').show();
					$('#usergroups').show();
				}
				else {
					$('#usergroups').show();
					$('#wsresources').show();
					$('#nb_operators_required').parent('td').parent('tr').show();
				}
			});
			jQuery("#type").trigger('change');
		});

	</script>
<?php

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Workstation")), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
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

	print '<tr id="usergroups"';
	print ' ><td>';
	print $langs->trans('Groups');
	print '</td>';
	print '<td>';
	print img_picto('', 'group');
	print $form->select_dolgroups($groups, 'groups', 1, '', 0, '', '', $object->entity, true, 'quatrevingtpercent widthcentpercentminusx');
	print '</td></tr>';

	print '<tr id="wsresources"><td>';
	print $langs->trans('Machines');
	print '</td>';
	print '<td>';
	print img_picto('', 'resource');
	print $formresource->select_resource_list($resources, 'resources', '', '', 0, '', '', $object->entity, true, 0, 'quatrevingtpercent widthcentpercentminusx', true);
	print '</td></tr>';

	// Other attributes
	//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Workstation"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
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

	print '<tr id="usergroups"';
	print '><td>';
	print $langs->trans('Groups');
	print '</td>';
	print '<td>';
	print img_picto('', 'group');
	print $form->select_dolgroups(empty($groups) ? $object->usergroups : $groups, 'groups', 1, '', 0, '', '', $object->entity, true, 'quatrevingtpercent widthcentpercentminusx');
	print '</td></tr>';

	print '<tr id="wsresources"><td>';
	print $langs->trans('Machines');
	print '</td>';
	print '<td>';
	print img_picto('', 'resource');
	print $formresource->select_resource_list(empty($resources) ? $object->resources : $resources, 'resources', '', '', 0, '', '', $object->entity, true, 0, 'quatrevingtpercent widthcentpercentminusx', true);
	print '</td></tr>';

	// Other attributes
	//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = workstationPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("Workstation"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteWorkstation'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx
	if ($action == 'enable') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('EnableAWorkstation'), $langs->trans("ConfirmEnableWorkstation", $object->ref), 'confirm_enable', $formquestion, 0, 1, 220);
	} elseif ($action == 'disable') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DisableAWorkstation'), $langs->trans("ConfirmDisableWorkstation", $object->ref), 'confirm_disable', $formquestion, 0, 1, 220);
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
	$linkback = '<a href="'.dol_buildpath('/workstation/workstation_list.php', 1).'?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	 // Ref customer
	 $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	 $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	 // Thirdparty
	 $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	 // Project
	 if (! empty($conf->project->enabled)) {
	 $langs->load("projects");
	 $morehtmlref .= '<br>'.$langs->trans('Project') . ' ';
	 if ($permissiontoadd) {
	 //if ($action != 'classify') $morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> ';
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
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	if ($object->type === 'MACHINE') {
		$object->fields['nb_operators_required']['visible'] = 0;
	}

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Groups
	if ($object->type !== 'MACHINE') {
		$toprint = array();
		$g = new UserGroup($db);
		foreach ($object->usergroups as $id_group) {
			$g->fetch($id_group);
			$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">' . $g->getNomUrl(1, '', 0, 'categtextwhite') . '</li>';
		}

		print '<tr><td>' . $langs->trans('Groups') . '</td><td>';
		print '<div class="select2-container-multi-dolibarr"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
		print '</td></tr>';
	}

	// Resources
	if ($object->type !== 'HUMAN') {
		$toprint = array();
		$r = new Dolresource($db);
		foreach ($object->resources as $id_resource) {
			$r->fetch($id_resource);
			$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">' . $r->getNomUrl(1, '', '', 0, 'categtextwhite') . '</li>';
		}

		print '<tr><td>' . $langs->trans('Machines') . '</td><td>';
		print '<div class="select2-container-multi-dolibarr"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
		print '</td></tr>';
	}

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

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
			// Modify
			if ($permissiontoadd) {
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>'."\n";
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Modify').'</a>'."\n";
			}

			// Clone
			if ($permissiontoadd) {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=clone&token='.newToken().'&object=workstation">'.$langs->trans("ToClone").'</a>'."\n";
			}


			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_ENABLED) {
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=disable&token='.newToken().'">'.$langs->trans("Disable").'</a>'."\n";
				} else {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=enable&token='.newToken().'">'.$langs->trans("Enable").'</a>'."\n";
				}
			}


			// Delete (need delete permission, or if draft, just need create/modify permission)
			if ($permissiontodelete) {
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans('Delete').'</a>'."\n";
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Delete').'</a>'."\n";
			}
		}
		print '</div>'."\n";
	}
}

// End of page
llxFooter();
$db->close();
