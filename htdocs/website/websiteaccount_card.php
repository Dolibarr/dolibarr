<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024 Lionel Vessiller	   <lvessiller@easya.solutions>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *   	\file       htdocs/website/websiteaccount_card.php
 *		\ingroup    website
 *		\brief      Page to create/edit/view thirdparty website account
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/website/lib/websiteaccount.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("companies", "website", "other"));

// Get parameters
$id         = GETPOSTINT('id');
$ref        = GETPOST('ref', 'alpha');
$action     = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize a technical objects
$object = new SocieteAccount($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array($object->element.'card', 'globalcard')); // Note that conf->hooks_modules contains array

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

// Security check
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$result = restrictedArea($user, 'website', $id);
$permissiontoaccess = (isModEnabled('website') && $user->hasRight('website', 'read')) || isModEnabled('webportal');
if (!$permissiontoaccess) {
	accessforbidden('NotAllowed');
}

// Permissions
$permissiontocreate = 0;
$permissiontodelete = 0;
// permissions from object type of site
if ($object->id > 0) {
	if ($object->site == 'dolibarr_website') {
		$permissiontocreate = isModEnabled('website') && $user->hasRight('website', 'write');
		$permissiontodelete = isModEnabled('website') && $user->hasRight('website', 'delete');
	} elseif ($object->site == 'dolibarr_portal') {
		$permissiontocreate = $permissiontodelete = isModEnabled('webportal') && $user->hasRight('webportal', 'write');
	}
} else {
	$permissiontocreate = isModEnabled('website') && $user->hasRight('website', 'write') || isModEnabled('webportal') && $user->hasRight('webportal', 'write');
}
$permissionnote    = $permissiontocreate;   //  Used by the include of actions_setnotes.inc.php
$permissiondellink = $permissiontocreate;   //  Used by the include of actions_dellink.inc.php
$permissiontoadd   = $permissiontocreate;   //  Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php

// check access from type of site on create, edit, delete (other than view)
$site_type_js = '';
if (!empty($action) && $action != 'view') {
	if (!empty($object->fields['site']['arrayofkeyval'])) {
		if (isset($object->fields['site']['arrayofkeyval']['dolibarr_website'])) {
			if ($action == 'delete' || $action == 'confirm_delete') {
				if (!$user->hasRight('website', 'delete')) {
					unset($object->fields['site']['arrayofkeyval']['dolibarr_website']);
				}
			} else {
				if (!$user->hasRight('website', 'write')) {
					unset($object->fields['site']['arrayofkeyval']['dolibarr_website']);
				}
			}
		}

		if (isset($object->fields['site']['arrayofkeyval']['dolibarr_portal'])) {
			if (!$user->hasRight('webportal', 'write')) {
				unset($object->fields['site']['arrayofkeyval']['dolibarr_portal']);
			}
		}
	}
	if (empty($object->fields['site']['arrayofkeyval'])) {
		accessforbidden('NotAllowed');
	}

	if ($object->id > 0) { // update or delete or other than create
		// check user has the right to modify this type of website
		if (!array_key_exists($object->site, $object->fields['site']['arrayofkeyval'])) {
			accessforbidden('NotAllowed');
		}
	}
}

$error = 0;


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = dol_buildpath('/societe/website.php', 1).'?id='.$object->fk_soc;

	if ($action == 'add' && !GETPOST('site')) {		// Test on permission not required
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Website")), null, 'errors');
		$action = 'create';
	}

	// Actions cancel, add, update or delete
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$triggersendname = 'WEBSITEACCOUNT_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_WEBSITEACCOUNT_TO';
	$trackid = 'websiteaccount'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$title = $langs->trans("WebsiteAccount");
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-website page-card_websiteaccount');

// prepare output js
$out_js = '';
if ($action == 'create' || $action == 'edit') {
	if (!empty($object->fields['site']['visible']) && !empty($object->fields['fk_website']['visible'])) {
		$site_type_js = 'function siteTypeChange(site_type) {';
		$site_type_js .= '		if (site_type == "dolibarr_website") {';
		$site_type_js .= '			jQuery("tr.field_fk_website").show();';
		$site_type_js .= '		} else {';
		$site_type_js .= '			jQuery("select#fk_website").val("-1").change();';
		$site_type_js .= '			jQuery("tr.field_fk_website").hide();';
		$site_type_js .= '		}';
		$site_type_js .= '}';
		$site_type_js .= 'jQuery(document).ready(function(){';
		$site_type_js .= '	siteTypeChange(jQuery("#site").val());';
		$site_type_js .= '	jQuery("#site").change(function(){';
		$site_type_js .= '		siteTypeChange(this.value);';
		$site_type_js .= '	});';
		$site_type_js .= '});';

		$out_js .= '<script type"text/javascript">';
		$out_js .= $site_type_js;
		$out_js .= '</script>';
	}
}

// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	print load_fiche_titre($langs->trans("NewWebsiteAccount", $langs->transnoentitiesnoconv("WebsiteAccount")));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if (!empty($backtopageforcancel)) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}
	if (!empty($backtopagejsfields)) {
		print '<input type="hidden" name="backtopagejsfields" value="'.$backtopagejsfields.'">';
	}
	if (!empty($dol_openinpopup)) {
		print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';
	}

	print dol_get_fiche_head(array(), '');

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	print $out_js;
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("WebsiteAccount"));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';

	print $out_js;
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	if ($object->fk_soc > 0 && empty($socid)) {
		$socid = $object->fk_soc;
	}

	//$res = $object->fetch_optionals();

	$head = websiteaccountPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("WebsiteAccount"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteWebsiteAccount'), $langs->trans('ConfirmDeleteWebsiteAccount').'<br>'.$langs->trans('ConfirmDeleteWebsiteAccount2'), 'confirm_delete', '', 0, 1);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm);
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
	$linkback = '';
	if ($socid) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/societe/website.php?socid='.$socid.'&restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToListForThirdParty").'</a>';
	}
	//if ($fk_website) {
	//	$linkback = '<a href="'.DOL_URL_ROOT.'/website/website_card.php?fk_website='.$fk_website.'&restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	//}

	$morehtmlref = '<div class="refidno">';
	/*
	// Ref bis
	$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->hasRight('website', 'write'), 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->hasRight('website', 'write'), 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
	// Project
	if (isModEnabled('project'))
	{
		$langs->load("projects");
		$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
		if ($user->hasRight('website', 'write'))
		{
			if ($action != 'classify')
			{
				$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
				if ($action == 'classify') {
					//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
					$morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
					$morehtmlref.='<input type="hidden" name="action" value="classin">';
					$morehtmlref.='<input type="hidden" name="token" value="'.newToken().'">';
					$morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
					$morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					$morehtmlref.='</form>';
				} else {
					$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
				}
			}
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
				$morehtmlref.=$proj->ref;
				$morehtmlref.='</a>';
			} else {
				$morehtmlref.='';
			}
		}
	}
	*/
	$morehtmlref .= '</div>';

	if ($socid > 0) {
		$object->next_prev_filter = 'te.fk_soc:=:'.((int) $socid);
	}

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'rowid', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$keyforbreak='note_private';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes
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
			// Send
			if (empty($user->socid)) {
				print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
			}

			// Clone
			if ($permissiontoadd) {
				print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid) ? '&socid='.$object->socid : '').'&action=clone&token='.newToken(), '', $permissiontoadd);
			}

			// Delete
			$params = array();
			print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $permissiontodelete, $params);
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

		print '</div><div class="fichehalfright">';

		/*
		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/mymodule/myobject_agenda.php', 1).'?id='.$object->id);

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element, $socid, 1, '', $MAXEVENT);
		*/

		print '</div></div>';
	}

	// Presend form
	$modelmail = 'websiteaccount';
	$defaulttopic = 'Information';
	$diroutput = isModEnabled('website') ? $conf->website->dir_output : '';
	$trackid = 'websiteaccount'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
