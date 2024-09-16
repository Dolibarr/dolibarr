<?php
/* Copyright (C) 2017-2020	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *    \file       htdocs/mrp/mo_card.php
 *    \ingroup    mrp
 *    \brief      Page to create/edit/view MO Manufacturing Order
 */


// Load Dolibarr environment
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
require_once DOL_DOCUMENT_ROOT.'/mrp/lib/mrp_mo.lib.php';
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
require_once DOL_DOCUMENT_ROOT.'/bom/lib/bom.lib.php';

if (isModEnabled('workstation')) {
	require_once DOL_DOCUMENT_ROOT.'/workstation/class/workstation.class.php';
}


// Load translation files required by the page
$langs->loadLangs(array('mrp', 'other'));


// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'mocard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$TBomLineId = GETPOST('bomlineid', 'array');
$lineid   = GETPOSTINT('lineid');
$socid = GETPOSTINT("socid");

// Initialize a technical objects
$object = new Mo($db);
$objectbom = new BOM($db);

$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->mrp->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('mocard', 'globalcard')); // Note that conf->hooks_modules contains array

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

if (GETPOSTINT('fk_bom') > 0) {
	$objectbom->fetch(GETPOSTINT('fk_bom'));

	if ($action != 'add') {
		// We force calling parameters if we are not in the submit of creation of MO
		$_POST['fk_product'] = $objectbom->fk_product;
		$_POST['qty'] = $objectbom->qty;
		$_POST['mrptype'] = $objectbom->bomtype;
		$_POST['fk_warehouse'] = $objectbom->fk_warehouse;
		$_POST['note_private'] = $objectbom->note_private;
	}
}

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'mrp', $object->id, 'mrp_mo', '', 'fk_soc', 'rowid', $isdraft);

// Permissions
$permissionnote = $user->hasRight('mrp', 'write'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('mrp', 'write'); // Used by the include of actions_dellink.inc.php
$permissiontoadd = $user->hasRight('mrp', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('mrp', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$upload_dir = $conf->mrp->multidir_output[isset($object->entity) ? $object->entity : 1];


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

	$backurlforlist = dol_buildpath('/mrp/mo_list.php', 1);

	$object->oldQty = $object->qty;

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/mrp/mo_card.php?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}
	if ($cancel && !empty($backtopageforcancel)) {
		$backtopage = $backtopageforcancel;
	}
	$triggermodname = 'MO_MODIFY'; // Name of trigger action code to execute when we modify record

	// Create MO with Children
	if ($action == 'add' && empty($id) && !empty($TBomLineId) && $permissiontoadd) {
		$noback = 1;
		include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

		$mo_parent = $object;

		$moline = new MoLine($db);
		$objectbomchildline = new BOMLine($db);

		foreach ($TBomLineId as $id_bom_line) {
			$object = new Mo($db);	// modified by the actions_addupdatedelete.inc.php

			$objectbomchildline->fetch($id_bom_line);

			$TMoLines = $moline->fetchAll('DESC', 'rowid', '1', '', array('origin_id' => $id_bom_line));

			foreach ($TMoLines as $tmpmoline) {
				$_POST['fk_bom'] = $objectbomchildline->fk_bom_child;
				$_POST['fk_parent_line'] = $tmpmoline->id;
				$_POST['qty'] = $tmpmoline->qty;
				$_POST['fk_product'] = $tmpmoline->fk_product;
			}

			include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

			$res = $object->add_object_linked('mo', $mo_parent->id);
		}

		header("Location: ".dol_buildpath('/mrp/mo_card.php?id='.((int) $mo_parent->id), 1));
		exit;
	} elseif ($action == 'confirm_cancel' && $confirm == 'yes' && !empty($permissiontoadd)) {
		$also_cancel_consumed_and_produced_lines = (GETPOST('alsoCancelConsumedAndProducedLines', 'alpha') ? 1 : 0);
		$result = $object->cancel($user, 0, $also_cancel_consumed_and_produced_lines);
		if ($result > 0) {
			header("Location: " . dol_buildpath('/mrp/mo_card.php?id=' . $object->id, 1));
			exit;
		} else {
			$action = '';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes' && !empty($permissiontodelete)) {
		$also_cancel_consumed_and_produced_lines = (GETPOST('alsoCancelConsumedAndProducedLines', 'alpha') ? 1 : 0);
		$result = $object->delete($user, 0, $also_cancel_consumed_and_produced_lines);
		if ($result > 0) {
			header("Location: " . $backurlforlist);
			exit;
		} else {
			$action = '';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'confirm_delete' && !empty($permissiontodelete)) {
		if (!($object->id > 0)) {
			dol_print_error(null, 'Error, object must be fetched before being deleted');
			exit;
		}

		$error = 0;
		$deleteChilds = GETPOST('deletechilds', 'aZ');

		// Start the database transaction
		$db->begin();

		if ($deleteChilds === 'on') {
			$TMoChildren = $object->getAllMoChilds();

			foreach ($TMoChildren as $id => $childObject) {
				if ($childObject->delete($user) == -1) {
					$error++;
					if (!empty($childObject->errors)) {
						setEventMessages(null, $childObject->errors, 'errors');
					} else {
						setEventMessages($childObject->error, null, 'errors');
					}
				}
			}
		}

		if (!$error) {
			$result = $object->delete($user);

			if ($result > 0) {
				setEventMessages("RecordDeleted", null, 'mesgs');

				if ($deleteChilds === 'on') {
					setEventMessages("MoChildsDeleted", null, 'mesgs');
				}

				if (empty($noback)) {
					header("Location: " . $backurlforlist);
					exit;
				}
			} else {
				$error++;
				if (!empty($object->errors)) {
					setEventMessages(null, $object->errors, 'errors');
				} else {
					setEventMessages($object->error, null, 'errors');
				}
			}
		}

		// Commit or rollback the database transaction based on whether there was an error
		if ($error) {
			$db->rollback();
		} else {
			$db->commit();
		}
	}


	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOSTINT('projectid'));
	}

	// Actions to send emails
	$triggersendname = 'MO_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_MO_TO';
	$trackid = 'mo'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be 'include', not 'include_once'

	// Action close produced
	if ($action == 'confirm_produced' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->setStatut($object::STATUS_PRODUCED, 0, '', 'MRP_MO_PRODUCED');
		if ($result >= 0) {
			// Define output language
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$object->generateDocument($model, $outputlangs, 0, 0, 0);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}




/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans('ManufacturingOrder')." - ".$langs->trans("Card");
$help_url = 'EN:Module_Manufacturing_Orders|FR:Module_Ordres_de_Fabrication|DE:Modul_Fertigungsauftrag';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-mrp page-card');



// Part to create
if ($action == 'create') {
	if (GETPOSTINT('fk_bom') > 0) {
		$titlelist = $langs->trans("ToConsume");
		if ($objectbom->bomtype == 1) {
			$titlelist = $langs->trans("ToObtain");
		}
	}

	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Mo")), '', 'mrp');

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

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	mrpCollapseBomManagement(); ?>
	<script>
		 $(document).ready(function () {
			 jQuery('#fk_bom').change(function() {
				console.log('We change value of BOM with BOM of id '+jQuery('#fk_bom').val());
				if (jQuery('#fk_bom').val() > 0)
				{
					// Redirect to page with fk_bom set
					window.location.href = '<?php echo $_SERVER["PHP_SELF"] ?>?action=create&token=<?php echo newToken(); ?>&fk_bom='+jQuery('#fk_bom').val();
					/*
					$.getJSON('<?php echo DOL_URL_ROOT ?>/mrp/ajax/ajax_bom.php?action=getBoms&idbom='+jQuery('#fk_bom').val(), function(data) {
						console.log(data);
						if (typeof data.rowid != "undefined") {
							console.log("New BOM loaded, we set values in form");
							console.log(data);
							$('#qty').val(data.qty);
							$("#mrptype").val(data.bomtype);	// We set bomtype into mrptype
							$('#mrptype').trigger('change'); // Notify any JS components that the value changed
							$("#fk_product").val(data.fk_product);
							$('#fk_product').trigger('change'); // Notify any JS components that the value changed
							$('#note_private').val(data.description);
							$('#note_private').trigger('change'); // Notify any JS components that the value changed
							$('#fk_warehouse').val(data.fk_warehouse);
							$('#fk_warehouse').trigger('change'); // Notify any JS components that the value changed
							if (typeof CKEDITOR != "undefined") {
								if (typeof CKEDITOR.instances != "undefined") {
									if (typeof CKEDITOR.instances.note_private != "undefined") {
										console.log(CKEDITOR.instances.note_private);
										CKEDITOR.instances.note_private.setData(data.description);
									}
								}
							}
						} else {
							console.log("Failed to get BOM");
						}
					});*/
				}
				else if (jQuery('#fk_bom').val() < 0) {
					// Redirect to page with all fields defined except fk_bom set
					console.log(jQuery('#fk_product').val());
					window.location.href = '<?php echo $_SERVER["PHP_SELF"] ?>?action=create&token=<?php echo newToken(); ?>&qty='+jQuery('#qty').val()+'&mrptype='+jQuery('#mrptype').val()+'&fk_product='+jQuery('#fk_product').val()+'&label='+jQuery('#label').val()+'&fk_project='+jQuery('#fk_project').val()+'&fk_warehouse='+jQuery('#fk_warehouse').val();
					/*
					$('#qty').val('');
					$("#fk_product").val('');
					$('#fk_product').trigger('change'); // Notify any JS components that the value changed
					$('#note_private').val('');
					$('#note_private').trigger('change'); // Notify any JS components that the value changed
					$('#fk_warehouse').val('');
					$('#fk_warehouse').trigger('change'); // Notify any JS components that the value changed
					*/
				}
			 });

			//jQuery('#fk_bom').trigger('change');
		})
	</script>
	<?php

	print $form->buttonsSaveCancel("Create");

	if ($objectbom->id > 0) {
		print load_fiche_titre($titlelist);

		print '<!-- list of product/services to consume -->'."\n";
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		$arrayOfMoLines = array();
		foreach ($objectbom->lines as $key => $val) {
			$moLine = new MoLine($db);
			$moLine->id = $objectbom->lines[$key]->id;
			$moLine->position = $objectbom->lines[$key]->position;
			$moLine->fk_product = $objectbom->lines[$key]->fk_product;
			$moLine->fk_unit = $objectbom->lines[$key]->fk_unit;
			$moLine->qty = $objectbom->lines[$key]->qty;
			$moLine->qty_frozen = $objectbom->lines[$key]->qty_frozen;
			$moLine->disable_stock_change = $objectbom->lines[$key]->disable_stock_change;

			$arrayOfMoLines[] = $moLine;
		}
		$object->lines = $arrayOfMoLines;
		$object->mrptype = $objectbom->bomtype;
		$object->bom = $objectbom;

		$object->printOriginLinesList('', array());

		print '</table>';
		print '</div>';
	}

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("ManufacturingOrder"), '', 'mrp');

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

	$object->fields['fk_bom']['disabled'] = 1;

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_thirdparty();

	$head = moPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("ManufacturingOrder"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$numberofmochilds = count($object->getAllMoChilds());

		if ($numberofmochilds > 0) {
			$label = $langs->trans("DeleteMoChild", '('.strval($numberofmochilds).')');
		} else {
			$label = $langs->trans("DeleteMoChild");
		}

		$formquestion = array(
			array('type' => 'checkbox', 'name' => 'deletechilds', 'label' => $label, 'value' => 0),
			array(
				'label' => $langs->trans('MoCancelConsumedAndProducedLines'),
				'name' => 'alsoCancelConsumedAndProducedLines',
				'type' => 'checkbox',
				'value' => !getDolGlobalString('MO_ALSO_CANCEL_CONSUMED_AND_PRODUCED_LINES_BY_DEFAULT') ? 0 : 1
			)
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteMo'), $langs->trans('ConfirmDeleteMo'), 'confirm_delete', $formquestion, 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Confirmation of validation
	if ($action == 'validate') {
		// We check that object has a temporary ref
		$ref = substr($object->ref, 1, 4);
		if ($ref == 'PROV') {
			$object->fetch_product();
			$numref = $object->getNextNumRef($object->product);
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans('ConfirmValidateMo', $numref);
		/*if (isModEnabled('notification'))
		 {
		 require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
		 $notify = new Notify($db);
		 $text .= '<br>';
		 $text .= $notify->confirmMessage('BOM_VALIDATE', $object->socid, $object);
		 }*/

		$formquestion = array();
		if (isModEnabled('mrp')) {
			$langs->load("mrp");
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct = new FormProduct($db);
			$forcecombo = 0;
			if ($conf->browser->name == 'ie') {
				$forcecombo = 1; // There is a bug in IE10 that make combo inside popup crazy
			}
			$formquestion = array(
			// 'text' => $langs->trans("ConfirmClone"),
			// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
			// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			);
		}

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Validate'), $text, 'confirm_validate', $formquestion, 0, 1, 220);
	}

	// Confirmation to cancel
	if ($action == 'cancel') {
		$formquestion = array(
			array(
				'label' => $langs->trans('MoCancelConsumedAndProducedLines'),
				'name' => 'alsoCancelConsumedAndProducedLines',
				'type' => 'checkbox',
				'value' => 0
			),
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CancelMo'), $langs->trans('ConfirmCancelMo'), 'confirm_cancel', $formquestion, 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneMo', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
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
	$linkback = '<a href="'.dol_buildpath('/mrp/mo_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	// Ref bis
	$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->mrp->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->mrp->creer, 'string', '', null, null, '', 1);*/
	// Thirdparty
	if (is_object($object->thirdparty)) {
		$morehtmlref .= $object->thirdparty->getNomUrl(1, 'customer');
		if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
		}
	}
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		if (is_object($object->thirdparty)) {
			$morehtmlref .= '<br>';
		}
		if ($permissiontoadd) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
	}
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	//Mo Parent
	$mo_parent = $object->getMoParent();
	if (is_object($mo_parent)) {
		print '<tr class="field_fk_mo_parent">';
		print '<td class="titlefield fieldname_fk_mo_parent">' . $langs->trans('ParentMo') . '</td>';
		print '<td class="valuefield fieldname_fk_mo_parent">' .$mo_parent->getNomUrl(1).'</td>';
		print '</tr>';
	}

	// Common attributes
	$keyforbreak = 'fk_warehouse';
	unset($object->fields['fk_project']);
	unset($object->fields['fk_soc']);
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Lines
	 */

	if (!empty($object->table_element_line)) {
		// Show object lines
		//$result = $object->getLinesArray();
		$object->fetchLines();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOSTINT('lineid')).'" method="POST">
    	<input type="hidden" name="token" value="' . newToken().'">
    	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
    	<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
    	<input type="hidden" name="id" value="' . $object->id.'">
    	';

		/*if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}*/

		if (!empty($object->lines)) {
			print '<div class="div-table-responsive-no-min">';
			print '<table id="tablelines" class="noborder noshadow" width="100%">';

			print '<tr class="liste_titre">';
			print '<td class="liste_titre">'.$langs->trans("Summary").'</td>';
			print '<td></td>';
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td>'.$langs->trans("ProductsToConsume").'</td>';
			print '<td>';
			if (!empty($object->lines)) {
				$i = 0;
				foreach ($object->lines as $line) {
					if ($line->role == 'toconsume') {
						if ($i) {
							print ', ';
						}
						$tmpproduct = new Product($db);
						$tmpproduct->fetch($line->fk_product);
						print $tmpproduct->getNomUrl(1);
						$i++;
					}
				}
			}
			print '</td>';
			print '</tr>';

			print '<tr class="oddeven">';
			print '<td>'.$langs->trans("ProductsToProduce").'</td>';
			print '<td>';
			if (!empty($object->lines)) {
				$i = 0;
				foreach ($object->lines as $line) {
					if ($line->role == 'toproduce') {
						if ($i) {
							print ', ';
						}
						$tmpproduct = new Product($db);
						$tmpproduct->fetch($line->fk_product);
						print $tmpproduct->getNomUrl(1);
						$i++;
					}
				}
			}
			print '</td>';
			print '</tr>';

			print '</table>';
			print '</div>';
		}

		print "</form>\n";
	}


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
			//if (empty($user->socid)) {
			//	print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a>'."\n";
			//}

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED) {
				if ($permissiontoadd) {
					// TODO Add test that production has not started
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken().'">'.$langs->trans("SetToDraft").'</a>';
				}
			}

			// Modify
			if ($object->status == $object::STATUS_DRAFT) {
				if ($permissiontoadd) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>'."\n";
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Modify').'</a>'."\n";
				}
			}

			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				if ($permissiontoadd) {
					if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate&token='.newToken().'">'.$langs->trans("Validate").'</a>';
					} else {
						$langs->load("errors");
						print '<a class="butActionRefused" href="" title="'.$langs->trans("ErrorAddAtLeastOneLineFirst").'">'.$langs->trans("Validate").'</a>';
					}
				}
			}

			// Clone
			if ($permissiontoadd) {
				print dolGetButtonAction($langs->trans("ToClone"), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid) ? '&socid='.$object->socid : "").'&action=clone&token='.newToken().'&object=mo', 'clone', $permissiontoadd);
			}

			// Cancel - Reopen
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED || $object->status == $object::STATUS_INPROGRESS) {
					$arrayproduced = $object->fetchLinesLinked('produced', 0);
					$nbProduced = 0;
					foreach ($arrayproduced as $lineproduced) {
						$nbProduced += $lineproduced['qty'];
					}
					if ($nbProduced > 0) {	// If production has started, we can close it
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_produced&confirm=yes&token='.newToken().'">'.$langs->trans("Close").'</a>'."\n";
					} else {
						print '<a class="butActionRefused" href="#" title="'.$langs->trans("GoOnTabProductionToProduceFirst", $langs->transnoentitiesnoconv("Production")).'">'.$langs->trans("Close").'</a>'."\n";
					}

					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel&token='.newToken().'">'.$langs->trans("Cancel").'</a>'."\n";
				}

				if ($object->status == $object::STATUS_PRODUCED || $object->status == $object::STATUS_CANCELED) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_reopen&confirm=yes&token='.newToken().'">'.$langs->trans("ReOpen").'</a>'."\n";
				}
			}

			// Delete
			print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $permissiontodelete);
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

		// Documents
		$objref = dol_sanitizeFileName($object->ref);
		$relativepath = $objref.'/'.$objref.'.pdf';
		$filedir = $conf->mrp->dir_output.'/'.$objref;
		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed = $user->hasRight('mrp', 'read'); // If you can read, you can build the PDF to read content
		$delallowed = $user->hasRight("mrp", "creer"); // If you can create/edit, you can remove a file on card
		print $formfile->showdocuments('mrp:mo', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $mysoc->default_lang);

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('mo'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem, false);


		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/mrp/mo_agenda.php?id='.$object->id);

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element, $socid, 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'mo';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->mrp->dir_output;
	$trackid = 'mo'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
