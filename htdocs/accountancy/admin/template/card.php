<?php
/* Copyright (C) 2024       AWeerWolf
 * Copyright (C) 2026       Alexandre Spangaro		<alexandre@inovea-conseil.com>
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
 *
 */

/**
 * \file    accountancy/admin/template/card.php
 * \ingroup accountancy
 * \brief   Page to create/edit/view bookkeeping template
 */

// Load Dolibarr environment
require '../../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeepingtemplate.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeepingtemplateline.class.php';

// Load translation files required by the page
$langs->loadLangs(array("accountancy", "other"));

// Set needed objects
$accountingaccount = new AccountingAccount($db);
$formaccounting = new FormAccounting($db);

// Get parameters
$id = GETPOST('id', 'int');
$code = GETPOST('code', 'alpha');
$lineid = GETPOSTINT('lineid');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'bookkeepingtemplatecard';
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
$object = new BookkeepingTemplate($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->accounting->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array($object->element . 'card', 'globalcard'));

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_' . $key, 'alpha')) {
		$search[$key] = GETPOST('search_' . $key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($code)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php';

if ($id > 0 && empty($object->id)) {
	dol_print_error($db, 'Failed to load object');
}

// Security check
$permissiontoread = $user->hasRight('accounting', 'chartofaccount');
$permissiontoadd = $user->hasRight('accounting', 'chartofaccount');
$permissiontodelete = $user->hasRight('accounting', 'chartofaccount');
$permissionnote = $user->hasRight('accounting', 'chartofaccount');
$permissiondellink = $user->hasRight('accounting', 'chartofaccount');

$upload_dir = $conf->accounting->multidir_output[isset($object->entity) ? $object->entity : 1];

if (!$permissiontoread) {
	accessforbidden();
}

$form = new Form($db);
$formfile = new FormFile($db);

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = DOL_URL_ROOT . '/accountancy/admin/template/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT . '/accountancy/admin/template/card.php?id=' . ((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'ACCOUNTING_BOOKKEEPINGTEMPLATE_MODIFY';

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT . '/core/actions_printing.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT . '/core/actions_builddoc.inc.php';

	// Actions to send emails
	$triggersendname = 'ACCOUNTING_BOOKKEEPINGTEMPLATE_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_BOOKKEEPINGTEMPLATE_TO';
	$trackid = 'bookkeepingtemplate' . $object->id;
	include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';
}

// Action to add a new line
if ($action == 'addline' && $permissiontoadd) {
	$error = 0;

	// Get line data from POST
	$general_account = GETPOST('general_account', 'alphanohtml');
	$subledger_account = GETPOST('subledger_account', 'alphanohtml');
	if ($subledger_account == '-1') {
		$subledger_account = null;
	}
	$subledger_label = GETPOST('subledger_label', 'alphanohtml');
	$operation_label = GETPOST('operation_label', 'alphanohtml');
	$debit = price2num(GETPOST('debit', 'alpha'));
	$credit = price2num(GETPOST('credit', 'alpha'));

	// Validation
	if (((float) $debit != 0.0) && ((float) $credit != 0.0)) {
		$error++;
		setEventMessages($langs->trans('ErrorDebitCredit'), null, 'errors');
		$action = 'edit';
	}

	if (empty($general_account) || $general_account == '-1') {
		$error++;
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AccountNumber")), null, 'errors');
		$action = 'edit';
	}

	if (!$error) {
		// Fetch account label
		$accountingaccount->fetch(0, $general_account, 1);
		$general_label = $accountingaccount->label;

		// Create line object
		$line = new BookkeepingTemplateLine($db);
		$line->fk_transaction_template = $object->id;
		$line->general_account = $general_account;
		$line->general_label = $general_label;
		$line->subledger_account = $subledger_account;
		$line->subledger_label = $subledger_label;
		$line->operation_label = $operation_label;
		$line->debit = $debit;
		$line->credit = $credit;

		$result = $line->create($user, 0);

		if ($result < 0) {
			$error++;
			setEventMessages($line->error, $line->errors, 'errors');
			$action = 'edit';
		} else {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $object->id);
			exit;
		}
	}
}

// Action to update a line
if ($action == 'updateline' && $permissiontoadd) {
	$error = 0;

	// Get line data from POST
	$general_account = GETPOST('general_account', 'alphanohtml');
	$subledger_account = GETPOST('subledger_account', 'alphanohtml');
	if ($subledger_account == '-1') {
		$subledger_account = null;
	}
	$subledger_label = GETPOST('subledger_label', 'alphanohtml');
	$operation_label = GETPOST('operation_label', 'alphanohtml');
	$debit = price2num(GETPOST('debit', 'alpha'));
	$credit = price2num(GETPOST('credit', 'alpha'));

	// Validation
	if (((float) $debit != 0.0) && ((float) $credit != 0.0)) {
		$error++;
		setEventMessages($langs->trans('ErrorDebitCredit'), null, 'errors');
		$action = 'editline';
	}

	if (empty($general_account) || $general_account == '-1') {
		$error++;
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AccountNumber")), null, 'errors');
		$action = 'editline';
	}

	if (!$error) {
		// Fetch account label
		$accountingaccount->fetch(0, $general_account, 1);
		$general_label = $accountingaccount->label;

		// Load and update line
		$line = new BookkeepingTemplateLine($db);
		$result = $line->fetch($lineid);

		if ($result > 0) {
			$line->general_account = $general_account;
			$line->general_label = $general_label;
			$line->subledger_account = $subledger_account;
			$line->subledger_label = $subledger_label;
			$line->operation_label = $operation_label;
			$line->debit = $debit;
			$line->credit = $credit;

			$result = $line->update($user, 0);

			if ($result < 0) {
				$error++;
				setEventMessages($line->error, $line->errors, 'errors');
				$action = 'editline';
			} else {
				setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
				header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $object->id);
				exit;
			}
		} else {
			$error++;
			setEventMessages($line->error, $line->errors, 'errors');
		}
	}
}

// Action to delete a line
if ($action == 'confirm_deleteline' && $confirm == 'yes' && $permissiontodelete) {
	$result = $object->deleteLine($user, $lineid);
	if ($result > 0) {
		setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
		header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $object->id);
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

/*
 * View
 */

$title = $langs->trans('BookkeepingTemplate') . " - " . $langs->trans('Card');
if ($action == 'create') {
	$title = $langs->trans("NewBookkeepingTemplate");
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-accountancy page-admin-template-card');

// Update fields properties in realtime
if (!empty($conf->use_javascript_ajax)) {
	print "\n" . '<script type="text/javascript">';
	print '$(document).ready(function () {
			function toggleSubledger() {
				var isCentral = $("#accountingaccount_number option:selected").data("centralized");
				console.log("the selected general ledger account is centralised?", isCentral);
				if (isCentral) {
					$("#subledger_account, #subledger_label").prop("disabled", false);
				} else {
					$("#subledger_account, #subledger_label").prop("disabled", true);
				}
			}

			toggleSubledger();

			$("#accountingaccount_number").on("change", toggleSubledger);
			$("#accountingaccount_number").on("select2:select", toggleSubledger);
		';
	print '	});' . "\n";
	print '	</script>' . "\n";
}

// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	print load_fiche_titre($title, '', 'object_' . $object->picto);

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
	}

	print dol_get_fiche_head(array(), '');

	print '<table class="border centpercent tableforfieldcreate">' . "\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

	print '</table>' . "\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	dol_set_focus('input[name="code"]');
}

// Part to edit record
if (($id || $code) && $action == 'edit') {
	print load_fiche_titre($langs->trans("BookkeepingTemplate"), '', 'object_' . $object->picto);

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
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

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$head = accountingTransactionTemplatePrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("BookkeepingTemplate"), -1, $object->picto, 0, '', '', 0, '', 1);

	$formconfirm = '';

	// Confirmation to delete template
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteBookkeepingTemplate'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}

	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->code), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action);
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	// Object card
	$linkback = '<a href="' . DOL_URL_ROOT . '/accountancy/admin/template/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'code', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">' . "\n";

	// Common attributes
	$keyforbreak = '';
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline' && $action != 'addline') {
		print '<div class="tabsAction">' . "\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Modify
			if ($permissiontoadd) {
				print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit&token=' . newToken(), '', $permissiontoadd);
			}

			// Clone
			if ($permissiontoadd) {
				print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=clone&token=' . newToken(), '', $permissiontoadd);
			}

			// Delete
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), '', $permissiontodelete);
		}
		print '</div>' . "\n";
	}

	// Lines section
	print '<div class="div-table-responsive-no-min">';
	if (!empty($object->table_element_line)) {
		// Show object lines
		$result = $object->getLinesArray();

		print '<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '' : '#line_' . GETPOST('lineid', 'int')) . '" method="POST">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">';
		print '<input type="hidden" name="mode" value="">';
		print '<input type="hidden" name="page_y" value="">';
		print '<input type="hidden" name="id" value="' . $object->id . '">';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			// Define required variables for ajaxrow.tpl.php
			$fk_element = 'fk_transaction_template';
			$table_element_line = $object->table_element_line;
			include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow centpercent">';

		// Show header
		print '<tr class="liste_titre">';
		print_liste_field_titre("AccountAccountingShort");
		print_liste_field_titre("SubledgerAccount");
		print_liste_field_titre("LabelOperation");
		print_liste_field_titre("AccountingDebit", "", "", "", "", 'class="right"');
		print_liste_field_titre("AccountingCredit", "", "", "", "", 'class="right"');
		print_liste_field_titre("Action", "", "", "", "", 'width="60"', "", "", 'center ');
		print "</tr>\n";

		$i = 0;

		// Show existing lines
		foreach ($object->lines as $line) {
			// Line in view mode
			if ($action != 'editline' || GETPOST('lineid', 'int') != $line->id) {
				print '<tr class="oddeven" id="row-' . $line->id . '">';
				print '<!-- td columns in display mode -->';
				$resultfetch = $accountingaccount->fetch(0, $line->general_account, true);
				print '<td>';
				if ($resultfetch > 0) {
					print $accountingaccount->getNomUrl(0, 1, 1, '', 0);
				} else {
					print dol_escape_htmltag($line->general_account).' <span class="warning">('.$langs->trans("AccountRemovedFromCurrentChartOfAccount").')</span>';
				}
				print '</td>';
				print '<td>'.length_accounta($line->subledger_account ?? '');
				if (!empty($line->subledger_label)) {
					print ' - <span class="opacitymedium">'.dol_escape_htmltag($line->subledger_label).'</span>';
				}
				print '</td>';
				print '<td>' . ($line->operation_label ? dol_escape_htmltag($line->operation_label) : '') . '</td>';
				print '<td class="right">' . ($line->debit ? price($line->debit) : '') . '</td>';
				print '<td class="right">' . ($line->credit ? price($line->credit) : '') . '</td>';

				// Edit link
				print '<td class="center">';
				if ($permissiontoadd) {
					print '<a class="editfielda reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=editline&token=' . newToken() . '&lineid=' . $line->id . '#line_' . $line->id . '">';
					print img_edit('', 0, 'class="marginrightonly"');
					print '</a> &nbsp;';
				}

				// Delete link
				if ($permissiontodelete) {
					print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=deleteline&token=' . newToken() . '&lineid=' . $line->id . '">';
					print img_delete();
					print '</a>';
				}
				print '</td>';
				print '</tr>';
			}

			// Line in edit mode
			if ($action == 'editline' && GETPOST('lineid', 'int') == $line->id) {
				print '<tr class="oddeven" data-lineid="'.((int) $line->id).'">';
				print '<input type="hidden" name="lineid" value="' . GETPOST('lineid', 'int') . '">';

				// Account number
				print '<td>';
				print $formaccounting->select_account((GETPOSTISSET("accountingaccount_number") ? GETPOST("accountingaccount_number", "alpha") : $line->general_account), 'general_account', 1, array(), 1, 1, 'minwidth200 maxwidth500');
				print '</td>';

				// Subledger account
				print '<td>';
				// TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because:
				// It does not use the setup of "key pressed" to select a thirdparty and this hang browser on large databases.
				// Also, it is not possible to use a value that is not in the list.
				// Also, the label is not automatically filled when a value is selected.
				if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
					print $formaccounting->select_auxaccount((GETPOSTISSET("subledger_account") ? GETPOST("subledger_account", "alpha") : $line->subledger_account), 'subledger_account', 1, 'maxwidth250', '', 'subledger_label');
				} else {
					print '<input type="text" class="maxwidth150" name="subledger_account" value="'.(GETPOSTISSET("subledger_account") ? GETPOST("subledger_account", "alpha") : $line->subledger_account).'" placeholder="'.dol_escape_htmltag($langs->trans("SubledgerAccount")).'">';
				}
				// Add also input for subledger label
				print '<br><input type="text" class="maxwidth150" name="subledger_label" value="'.(GETPOSTISSET("subledger_label") ? GETPOST("subledger_label", "alpha") : $line->subledger_label).'" placeholder="'.dol_escape_htmltag($langs->trans("SubledgerAccountLabel")).'">';
				print '</td>';

				// Operation label
				print '<td>';
				print '<input type="text" class="minwidth200" name="operation_label" value="'.(GETPOSTISSET("operation_label") ? GETPOST("operation_label", "alpha") : $line->operation_label).'">';
				print '</td>';

				// Debit
				print '<td class="right">';
				print '<input type="text" name="debit" class="flat right maxwidth75" value="' . ($line->debit ? price($line->debit) : '') . '">';
				print '</td>';

				// Credit
				print '<td class="right">';
				print '<input type="text" name="credit" class="flat right maxwidth75" value="' . ($line->credit ? price($line->credit) : '') . '">';
				print '</td>';

				// Save button
				print '<td class="center" colspan="2">';
				print '<input type="submit" class="button buttongen marginbottomonly button-save" name="save" value="' . $langs->trans("Save") . '">';
				print '<br>';
				print '<input type="submit" class="button buttongen marginbottomonly button-cancel" name="cancel" value="' . $langs->trans("Cancel") . '">';
				print '</td>';

				print '</tr>';
			}

			$i++;
		}

		// Form to add new line
		if ($action != 'editline' && $permissiontoadd) {
			print '<tr class="liste_titre nodrag nodrop">';
			print '<td>';
			print $formaccounting->select_account('', 'general_account', 1, [], 1, 1, 'maxwidth300');
			print '</td>';
			print '<td>';
			// TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because:
			// It does not use the setup of "key pressed" to select a thirdparty and this hang browser on large databases.
			// Also, it is not possible to use a value that is not in the list.
			// Also, the label is not automatically filled when a value is selected.
			if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
				print $formaccounting->select_auxaccount('', 'subledger_account', 1, 'maxwidth250', '', 'subledger_label');
			} else {
				print '<input type="text" class="maxwidth150" name="new_subledger_account" value="" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccount")) . '">';
			}
			print '<br><input type="text" class="maxwidth150" name="new_subledger_label" value="" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccountLabel")) . '">';
			print '</td>';
			print '<td><input type="text" name="operation_label" class="flat minwidth150"></td>';
			print '<td class="right"><input type="text" name="debit" class="flat right maxwidth75"></td>';
			print '<td class="right"><input type="text" name="credit" class="flat right maxwidth75"></td>';
			print '<td class="center" colspan="2"><input type="submit" class="button buttongen marginbottomonly" name="addline" value="' . $langs->trans("Add") . '"></td>';
			print '</tr>';
		}

		print '</table>';
		print '</div>';

		print '</form>';
	}
	print '</div>';
}

// End of page
llxFooter();
$db->close();
