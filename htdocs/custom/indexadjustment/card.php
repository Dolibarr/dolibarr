<?php
/* Copyright (C) 2025 Florian Hödl <florian@hoedl.co>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       card.php
 * \ingroup    indexadjustment
 * \brief      Detail page for Index Adjustment
 */

require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/class/indexadjustment.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/class/indexadjustment_service.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/lib/indexadjustment.lib.php';

// Load translations
$langs->loadLangs(array("indexadjustment@indexadjustment", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Security check
if (!$user->hasRight('indexadjustment', 'indexadjustment', 'read')) {
	accessforbidden();
}

// Load object
$object = new IndexAdjustment($db);
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
}

$service = new IndexAdjustmentService($db);

/*
 * Actions
 */

if ($action == 'confirm_validate' && $confirm == 'yes' && $user->hasRight('indexadjustment', 'indexadjustment', 'write')) {
	$result = $object->validate($user);
	if ($result > 0) {
		setEventMessages($langs->trans("RecordValidated"), null, 'mesgs');
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == 'confirm_cancel' && $confirm == 'yes' && $user->hasRight('indexadjustment', 'indexadjustment', 'write')) {
	$result = $object->cancel($user);
	if ($result > 0) {
		setEventMessages($langs->trans("RecordCancelled"), null, 'mesgs');
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == 'confirm_rollback' && $confirm == 'yes' && $user->hasRight('indexadjustment', 'indexadjustment', 'rollback')) {
	$result = $service->rollback($object, $user);
	if ($result > 0) {
		setEventMessages($langs->trans("AdjustmentRolledBackSuccessfully"), null, 'mesgs');
	} else {
		setEventMessages($service->error, $service->errors, 'errors');
	}
	$object->fetch($id);
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$title = $langs->trans("IndexAdjustment") . " - " . $object->ref;
llxHeader('', $title);

if ($object->id > 0) {
	// Confirmation dialogs
	if ($action == 'validate') {
		print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ValidateIndexAdjustment'), $langs->trans('ConfirmValidateIndexAdjustment'), 'confirm_validate', '', 0, 1);
	}
	if ($action == 'cancel') {
		print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CancelIndexAdjustment'), $langs->trans('ConfirmCancelIndexAdjustment'), 'confirm_cancel', '', 0, 1);
	}
	if ($action == 'rollback') {
		print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RollbackIndexAdjustment'), $langs->trans('ConfirmRollbackIndexAdjustment'), 'confirm_rollback', '', 0, 1);
	}

	$head = indexadjustmentPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("IndexAdjustment"), -1, $object->picto);

	// Object info
	$linkback = '<a href="' . dol_buildpath('/indexadjustment/list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Label
	print '<tr><td class="titlefield">' . $langs->trans("Label") . '</td><td>' . dol_escape_htmltag($object->label) . '</td></tr>';

	// Adjustment Date
	print '<tr><td>' . $langs->trans("AdjustmentDate") . '</td><td>' . dol_print_date($object->adjustment_date, 'day') . '</td></tr>';

	// Adjustment Percent
	$percentDisplay = ($object->adjustment_percent >= 0 ? '+' : '') . number_format($object->adjustment_percent, 2) . '%';
	print '<tr><td>' . $langs->trans("AdjustmentPercent") . '</td><td><strong>' . $percentDisplay . '</strong></td></tr>';

	// Third Party
	if ($object->fk_soc > 0) {
		$thirdparty = new Societe($db);
		$thirdparty->fetch($object->fk_soc);
		print '<tr><td>' . $langs->trans("ThirdParty") . '</td><td>' . $thirdparty->getNomUrl(1) . '</td></tr>';
	} else {
		print '<tr><td>' . $langs->trans("ThirdParty") . '</td><td>' . $langs->trans("AllCustomers") . '</td></tr>';
	}

	// VPI info
	if ($object->vpi_base_year) {
		print '<tr><td>' . $langs->trans("VPIBaseYear") . '</td><td>' . $object->vpi_base_year . ' (' . $object->vpi_base_value . ')</td></tr>';
		print '<tr><td>' . $langs->trans("VPICurrentYear") . '</td><td>' . $object->vpi_current_year . ' (' . $object->vpi_current_value . ')</td></tr>';
	}

	print '</table>';
	print '</div>';

	print '<div class="fichehalfright">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Statistics (if executed)
	if ($object->status >= IndexAdjustment::STATUS_EXECUTED) {
		print '<tr><td class="titlefield">' . $langs->trans("TotalContracts") . '</td><td>' . $object->total_contracts . '</td></tr>';
		print '<tr><td>' . $langs->trans("TotalLines") . '</td><td>' . $object->total_lines . '</td></tr>';
		print '<tr><td>' . $langs->trans("TotalHTBefore") . '</td><td>' . price($object->total_ht_before) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td></tr>';
		print '<tr><td>' . $langs->trans("TotalHTAfter") . '</td><td>' . price($object->total_ht_after) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td></tr>';

		$diff = $object->total_ht_after - $object->total_ht_before;
		$diffClass = $diff >= 0 ? 'amountremaintopay' : 'amountpaymentcomplete';
		print '<tr><td>' . $langs->trans("PriceDiff") . '</td><td><span class="' . $diffClass . '">' . ($diff >= 0 ? '+' : '') . price($diff) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</span></td></tr>';
	}

	// Executed by
	if ($object->date_executed) {
		print '<tr><td>' . $langs->trans("ExecutedOn") . '</td><td>' . dol_print_date($object->date_executed, 'dayhour') . '</td></tr>';
		if ($object->fk_user_executed) {
			$userexec = new User($db);
			$userexec->fetch($object->fk_user_executed);
			print '<tr><td>' . $langs->trans("ExecutedBy") . '</td><td>' . $userexec->getNomUrl(1) . '</td></tr>';
		}
	}

	// Date creation
	print '<tr><td>' . $langs->trans("DateCreation") . '</td><td>' . dol_print_date($object->datec, 'dayhour') . '</td></tr>';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	// Lines table (if executed)
	if ($object->status >= IndexAdjustment::STATUS_EXECUTED && !empty($object->lines)) {
		print '<br>';
		print '<div class="div-table-responsive">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>' . $langs->trans("Contract") . '</th>';
		print '<th>' . $langs->trans("Product") . '</th>';
		print '<th class="right">' . $langs->trans("SubpriceBefore") . '</th>';
		print '<th class="right">' . $langs->trans("SubpriceAfter") . '</th>';
		print '<th class="right">' . $langs->trans("PriceDiff") . '</th>';
		print '<th class="center">' . $langs->trans("Rollback") . '</th>';
		print '</tr>';

		foreach ($object->lines as $line) {
			print '<tr class="oddeven">';

			// Contract
			$contract = new Contrat($db);
			$contract->fetch($line->fk_contrat);
			print '<td>' . $contract->getNomUrl(1) . '</td>';

			// Product
			print '<td>' . dol_escape_htmltag($line->product_label) . '</td>';

			// Before
			print '<td class="right">' . price($line->subprice_before) . '</td>';

			// After
			print '<td class="right">' . price($line->subprice_after) . '</td>';

			// Diff
			$diffClass = $line->price_diff_ht >= 0 ? 'amountremaintopay' : 'amountpaymentcomplete';
			print '<td class="right"><span class="' . $diffClass . '">' . ($line->price_diff_ht >= 0 ? '+' : '') . price($line->price_diff_ht) . '</span></td>';

			// Rollback status
			print '<td class="center">';
			if ($line->rollback_executed) {
				print '<span class="badge badge-warning">' . $langs->trans("RolledBack") . '</span>';
			} else {
				print '-';
			}
			print '</td>';

			print '</tr>';
		}

		print '</table>';
		print '</div>';
	}

	// Action buttons
	print '<div class="tabsAction">';

	if ($object->status == IndexAdjustment::STATUS_DRAFT) {
		if ($user->hasRight('indexadjustment', 'indexadjustment', 'write')) {
			print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=validate&token=' . newToken(), '', true);
		}
	}

	if ($object->status == IndexAdjustment::STATUS_VALIDATED) {
		if ($user->hasRight('indexadjustment', 'indexadjustment', 'execute')) {
			print dolGetButtonAction($langs->trans('Execute'), '', 'default', dol_buildpath('/indexadjustment/wizard.php', 1) . '?id=' . $object->id . '&action=execute', '', true);
		}
	}

	if ($object->status == IndexAdjustment::STATUS_EXECUTED) {
		if ($user->hasRight('indexadjustment', 'indexadjustment', 'rollback') && $service->canRollback($object)) {
			print dolGetButtonAction($langs->trans('Rollback'), '', 'delete', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=rollback&token=' . newToken(), '', true);
		}
	}

	if ($object->status == IndexAdjustment::STATUS_DRAFT || $object->status == IndexAdjustment::STATUS_VALIDATED) {
		if ($user->hasRight('indexadjustment', 'indexadjustment', 'write')) {
			print dolGetButtonAction($langs->trans('Cancel'), '', 'delete', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=cancel&token=' . newToken(), '', true);
		}
	}

	print '</div>';
} else {
	print $langs->trans("RecordNotFound");
}

llxFooter();
$db->close();
