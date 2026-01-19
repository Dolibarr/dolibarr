<?php
/* Copyright (C) 2025 Florian Hödl <florian@hoedl.co>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       wizard.php
 * \ingroup    indexadjustment
 * \brief      Wizard for creating and executing index adjustments
 */

require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/class/indexadjustment.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/class/indexadjustment_service.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/lib/indexadjustment.lib.php';

// Load translations
$langs->loadLangs(array("indexadjustment@indexadjustment", "contracts", "other"));

// Security check
if (!$user->hasRight('indexadjustment', 'indexadjustment', 'write')) {
	accessforbidden();
}

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$step = GETPOST('step', 'int') ?: 1;
$confirm = GETPOST('confirm', 'alpha');

// Form data
$label = GETPOST('label', 'alphanohtml');
$adjustment_percent = GETPOST('adjustment_percent', 'alpha');
$adjustment_date = dol_mktime(0, 0, 0, GETPOST('adjustment_datemonth', 'int'), GETPOST('adjustment_dateday', 'int'), GETPOST('adjustment_dateyear', 'int'));
$fk_soc = GETPOST('fk_soc', 'int');
$selected_contracts = GETPOST('contracts', 'array');

// Initialize objects
$object = new IndexAdjustment($db);
$service = new IndexAdjustmentService($db);

if ($id > 0) {
	$object->fetch($id);
}

/*
 * Actions
 */

// Step 1: Create adjustment object
if ($action == 'create' && $step == 1) {
	if (empty($label)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesaliases("Label")), null, 'errors');
		$action = '';
	} elseif ($adjustment_percent === '' || $adjustment_percent === null) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesaliases("AdjustmentPercent")), null, 'errors');
		$action = '';
	} else {
		$object->label = $label;
		$object->adjustment_percent = (float)str_replace(',', '.', $adjustment_percent);
		$object->adjustment_date = $adjustment_date ?: dol_now();
		$object->fk_soc = $fk_soc > 0 ? $fk_soc : null;

		$result = $object->create($user);
		if ($result > 0) {
			header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id . "&step=2");
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

// Step 2: Validate and go to preview
if ($action == 'preview' && $step == 2 && $id > 0) {
	if (empty($selected_contracts)) {
		setEventMessages($langs->trans("SelectAtLeastOneContract"), null, 'errors');
	} else {
		// Store selected contracts in session
		$_SESSION['indexadjustment_contracts_' . $id] = $selected_contracts;
		header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id . "&step=3");
		exit;
	}
}

// Step 3: Execute adjustment
if ($action == 'confirm_execute' && $confirm == 'yes' && $step == 3 && $id > 0) {
	if (!$user->hasRight('indexadjustment', 'indexadjustment', 'execute')) {
		accessforbidden();
	}

	$contractIds = isset($_SESSION['indexadjustment_contracts_' . $id]) ? $_SESSION['indexadjustment_contracts_' . $id] : array();

	if (empty($contractIds)) {
		setEventMessages($langs->trans("NoContractsSelected"), null, 'errors');
	} else {
		// Validate first
		$object->validate($user);

		// Execute
		$result = $service->execute($object, $user, $contractIds);
		if ($result > 0) {
			unset($_SESSION['indexadjustment_contracts_' . $id]);
			setEventMessages($langs->trans("AdjustmentExecutedSuccessfully"), null, 'mesgs');
			header("Location: " . dol_buildpath('/indexadjustment/card.php', 1) . "?id=" . $id);
			exit;
		} else {
			setEventMessages($service->error, $service->errors, 'errors');
		}
	}
}

/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);

$title = $langs->trans("NewIndexAdjustment");
llxHeader('', $title);

print load_fiche_titre($title, '', 'fa-percent');

// Progress steps
print '<div class="opacitymedium marginbottomonly">';
print '<span class="' . ($step >= 1 ? 'badge badge-primary' : 'badge') . '">1. ' . $langs->trans("WizardStep1") . '</span> ';
print '<span class="' . ($step >= 2 ? 'badge badge-primary' : 'badge') . '">2. ' . $langs->trans("WizardStep2") . '</span> ';
print '<span class="' . ($step >= 3 ? 'badge badge-primary' : 'badge') . '">3. ' . $langs->trans("WizardStep3") . '</span> ';
print '<span class="' . ($step >= 4 ? 'badge badge-primary' : 'badge') . '">4. ' . $langs->trans("WizardStep4") . '</span>';
print '</div>';

// Step 1: Settings
if ($step == 1) {
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="create">';
	print '<input type="hidden" name="step" value="1">';

	print '<table class="border centpercent">';

	// Label
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("Label") . '</td>';
	print '<td><input type="text" class="flat minwidth300" name="label" value="' . dol_escape_htmltag($label ?: $langs->trans("IndexAdjustment") . ' ' . date('Y')) . '"></td></tr>';

	// Adjustment Percent
	print '<tr><td class="fieldrequired">' . $langs->trans("AdjustmentPercent") . '</td>';
	print '<td><input type="text" class="flat width100" name="adjustment_percent" value="' . dol_escape_htmltag($adjustment_percent ?: '4.5') . '"> %';
	print ' <span class="opacitymedium">(' . $langs->trans("EnterAdjustmentPercent") . ')</span></td></tr>';

	// Adjustment Date
	print '<tr><td>' . $langs->trans("AdjustmentDate") . '</td>';
	print '<td>' . $form->selectDate($adjustment_date ?: dol_now(), 'adjustment_date', 0, 0, 0, '', 1, 1) . '</td></tr>';

	// Customer
	print '<tr><td>' . $langs->trans("ThirdParty") . '</td>';
	print '<td>' . $form->select_company($fk_soc, 'fk_soc', 'client > 0', 1, 0, 0, array(), 0, 'minwidth300');
	print ' <span class="opacitymedium">(' . $langs->trans("SelectCustomerOrAll") . ')</span></td></tr>';

	print '</table>';

	print '<br>';
	print '<div class="center">';
	print '<input type="submit" class="button button-save" value="' . $langs->trans("Next") . ' &raquo;">';
	print '</div>';

	print '</form>';
}

// Step 2: Select Contracts
if ($step == 2 && $id > 0) {
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="preview">';
	print '<input type="hidden" name="id" value="' . $id . '">';
	print '<input type="hidden" name="step" value="2">';

	// Show adjustment info
	print '<div class="info">';
	print '<strong>' . $langs->trans("Label") . ':</strong> ' . dol_escape_htmltag($object->label) . '<br>';
	print '<strong>' . $langs->trans("AdjustmentPercent") . ':</strong> ' . ($object->adjustment_percent >= 0 ? '+' : '') . number_format($object->adjustment_percent, 2) . '%<br>';
	print '</div>';

	// Get available contracts
	$contracts = $service->fetchActiveContracts($object->fk_soc);

	if (empty($contracts)) {
		print '<div class="warning">' . $langs->trans("NoContractsFound") . '</div>';
	} else {
		print '<p>' . $langs->trans("FilterActiveServicesOnly") . '</p>';

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th class="center" width="30"><input type="checkbox" id="checkall" onclick="$(\'input[name=contracts\\[\\]]\').prop(\'checked\', this.checked);"></th>';
		print '<th>' . $langs->trans("Contract") . '</th>';
		print '<th>' . $langs->trans("ThirdParty") . '</th>';
		print '<th>' . $langs->trans("RefCustomer") . '</th>';
		print '</tr>';

		$previousContracts = isset($_SESSION['indexadjustment_contracts_' . $id]) ? $_SESSION['indexadjustment_contracts_' . $id] : array();

		foreach ($contracts as $contractId => $contractData) {
			$checked = in_array($contractId, $previousContracts) ? 'checked' : '';

			print '<tr class="oddeven">';
			print '<td class="center"><input type="checkbox" name="contracts[]" value="' . $contractId . '" ' . $checked . '></td>';
			print '<td><a href="' . DOL_URL_ROOT . '/contrat/card.php?id=' . $contractId . '">' . dol_escape_htmltag($contractData['ref']) . '</a></td>';
			print '<td>' . dol_escape_htmltag($contractData['socname']) . '</td>';
			print '<td>' . dol_escape_htmltag($contractData['ref_customer']) . '</td>';
			print '</tr>';
		}

		print '</table>';
	}

	print '<br>';
	print '<div class="center">';
	print '<a class="button" href="' . $_SERVER['PHP_SELF'] . '?step=1">&laquo; ' . $langs->trans("Back") . '</a> ';
	if (!empty($contracts)) {
		print '<input type="submit" class="button button-save" value="' . $langs->trans("PreviewAdjustment") . ' &raquo;">';
	}
	print '</div>';

	print '</form>';
}

// Step 3: Preview and Execute
if ($step == 3 && $id > 0) {
	$contractIds = isset($_SESSION['indexadjustment_contracts_' . $id]) ? $_SESSION['indexadjustment_contracts_' . $id] : array();

	if (empty($contractIds)) {
		print '<div class="error">' . $langs->trans("NoContractsSelected") . '</div>';
		print '<a class="button" href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&step=2">&laquo; ' . $langs->trans("Back") . '</a>';
	} else {
		// Calculate preview
		$preview = $service->previewAdjustments($contractIds, $object->adjustment_percent);

		// Confirmation dialog
		if ($action == 'execute') {
			print $form->formconfirm(
				$_SERVER["PHP_SELF"] . '?id=' . $id . '&step=3',
				$langs->trans('ExecuteIndexAdjustment'),
				$langs->trans('ConfirmExecuteIndexAdjustment'),
				'confirm_execute',
				'',
				0,
				1
			);
		}

		// Summary
		print '<div class="info">';
		print '<strong>' . $langs->trans("Label") . ':</strong> ' . dol_escape_htmltag($object->label) . '<br>';
		print '<strong>' . $langs->trans("AdjustmentPercent") . ':</strong> ' . ($object->adjustment_percent >= 0 ? '+' : '') . number_format($object->adjustment_percent, 2) . '%<br>';
		print '<strong>' . $langs->trans("TotalContracts") . ':</strong> ' . $preview['totals']['total_contracts'] . '<br>';
		print '<strong>' . $langs->trans("TotalLines") . ':</strong> ' . $preview['totals']['total_lines'] . '<br>';
		print '</div>';

		// Totals summary
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">' . $langs->trans("Summary") . '</th>';
		print '</tr>';
		print '<tr class="oddeven"><td>' . $langs->trans("TotalHTBefore") . '</td><td class="right">' . price($preview['totals']['total_ht_before']) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td></tr>';
		print '<tr class="oddeven"><td>' . $langs->trans("TotalHTAfter") . '</td><td class="right">' . price($preview['totals']['total_ht_after']) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td></tr>';

		$diffClass = $preview['totals']['total_diff'] >= 0 ? 'amountremaintopay' : 'amountpaymentcomplete';
		print '<tr class="oddeven"><td><strong>' . $langs->trans("PriceDiff") . '</strong></td><td class="right"><strong><span class="' . $diffClass . '">' . ($preview['totals']['total_diff'] >= 0 ? '+' : '') . price($preview['totals']['total_diff']) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</span></strong></td></tr>';
		print '</table>';

		// Detail per contract
		print '<br>';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>' . $langs->trans("Contract") . '</th>';
		print '<th>' . $langs->trans("Product") . '</th>';
		print '<th class="right">' . $langs->trans("SubpriceBefore") . '</th>';
		print '<th class="right">' . $langs->trans("SubpriceAfter") . '</th>';
		print '<th class="right">' . $langs->trans("PriceDiff") . '</th>';
		print '</tr>';

		foreach ($preview['contracts'] as $contractId => $contractData) {
			$contract = new Contrat($db);
			$contract->fetch($contractId);

			foreach ($contractData['lines'] as $lineData) {
				print '<tr class="oddeven">';
				print '<td>' . $contract->getNomUrl(1) . '</td>';
				print '<td>' . dol_escape_htmltag($lineData['product_label']) . '</td>';
				print '<td class="right">' . price($lineData['subprice_before']) . '</td>';
				print '<td class="right">' . price($lineData['subprice_after']) . '</td>';

				$diffClass = $lineData['price_diff'] >= 0 ? 'amountremaintopay' : 'amountpaymentcomplete';
				print '<td class="right"><span class="' . $diffClass . '">' . ($lineData['price_diff'] >= 0 ? '+' : '') . price($lineData['price_diff']) . '</span></td>';
				print '</tr>';
			}
		}

		print '</table>';

		print '<br>';
		print '<div class="center">';
		print '<a class="button" href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&step=2">&laquo; ' . $langs->trans("Back") . '</a> ';
		if ($user->hasRight('indexadjustment', 'indexadjustment', 'execute')) {
			print '<a class="button button-save" href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&step=3&action=execute&token=' . newToken() . '">' . $langs->trans("ExecuteIndexAdjustment") . '</a>';
		}
		print '</div>';
	}
}

llxFooter();
$db->close();
