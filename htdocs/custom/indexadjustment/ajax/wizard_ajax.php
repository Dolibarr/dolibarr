<?php
/* Copyright (C) 2025 Florian Hödl <florian@hoedl.co>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       ajax/wizard_ajax.php
 * \ingroup    indexadjustment
 * \brief      AJAX endpoint for index adjustment wizard actions
 */

define('NOTOKENRENEWAL', '1');
define('NOREQUIREMENU', '1');
define('NOREQUIREHTML', '1');
define('NOREQUIREAJAX', '1');

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/class/indexadjustment.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/class/indexadjustment_service.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

// Load translations
$langs->loadLangs(array("indexadjustment@indexadjustment", "contracts"));

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(array('success' => false, 'message' => 'Method not allowed'));
	exit;
}

// CSRF token validation
$token = GETPOST('token', 'alpha');
$sessionToken = !empty($_SESSION['newtoken']) ? $_SESSION['newtoken'] : (!empty($_SESSION['token']) ? $_SESSION['token'] : '');
if (empty($token) || $token !== $sessionToken) {
	http_response_code(403);
	echo json_encode(array('success' => false, 'message' => 'Invalid security token'));
	exit;
}

// Security check - minimum write permission required
if (!$user->admin && !$user->hasRight('indexadjustment', 'indexadjustment', 'write')) {
	http_response_code(403);
	echo json_encode(array('success' => false, 'message' => 'Access denied'));
	exit;
}

header('Content-Type: application/json');

$wizardaction = GETPOST('wizardaction', 'aZ09');
$service = new IndexAdjustmentService($db);

$response = array('success' => false, 'message' => 'Unknown action');

switch ($wizardaction) {
	case 'create':
		$response = actionCreate($db, $user, $langs);
		break;

	case 'fetch_contracts':
		$response = actionFetchContracts($db, $service);
		break;

	case 'fetch_lines':
		$response = actionFetchLines($db, $service);
		break;

	case 'preview':
		$response = actionPreview($service);
		break;

	case 'preview_lines':
		$response = actionPreviewLines($service);
		break;

	case 'execute':
		$response = actionExecute($db, $user, $service);
		break;

	case 'execute_lines':
		$response = actionExecuteLines($db, $user, $service);
		break;
}

echo json_encode($response);
exit;


/**
 * Create a new IndexAdjustment object
 *
 * @param DoliDB $db    Database handler
 * @param User   $user  Current user
 * @param Translate $langs Language object
 * @return array Response data
 */
function actionCreate($db, $user, $langs)
{
	$label = GETPOST('label', 'alphanohtml');
	$adjustment_percent = GETPOST('adjustment_percent', 'alpha');
	$adjustment_date = dol_mktime(0, 0, 0, GETPOST('adjustment_datemonth', 'int'), GETPOST('adjustment_dateday', 'int'), GETPOST('adjustment_dateyear', 'int'));
	$fk_soc = GETPOST('fk_soc', 'int');

	if (empty($label)) {
		return array('success' => false, 'message' => $langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")));
	}
	if ($adjustment_percent === '' || $adjustment_percent === null) {
		return array('success' => false, 'message' => $langs->trans("ErrorFieldRequired", $langs->transnoentities("AdjustmentPercent")));
	}

	$object = new IndexAdjustment($db);
	$object->label = $label;
	$object->adjustment_percent = (float)str_replace(',', '.', $adjustment_percent);
	$object->adjustment_date = $adjustment_date ?: dol_now();
	$object->fk_soc = $fk_soc > 0 ? $fk_soc : null;

	// Enable SQL logging
	$db->lasterror();  // Clear any previous error

	$result = $object->create($user);
	if ($result > 0) {
		return array(
			'success' => true,
			'id' => $object->id,
			'ref' => $object->ref,
		);
	}

	// Collect all error info for debugging
	$errorMsg = 'Creation failed';
	if (!empty($object->error)) {
		$errorMsg = $object->error;
	}
	if (!empty($object->errors) && is_array($object->errors)) {
		$errorMsg .= ' | ' . implode(' | ', $object->errors);
	}
	// Include DB error if available
	if (!empty($db->lasterror())) {
		$errorMsg .= ' | DB: ' . $db->lasterror();
	}
	// Include last SQL query for debugging
	if (!empty($db->lastquery)) {
		$errorMsg .= ' | SQL: ' . substr($db->lastquery, 0, 500);
	}

	return array('success' => false, 'message' => $errorMsg);
}

/**
 * Fetch active contracts for selection
 *
 * @param DoliDB                 $db      Database handler
 * @param IndexAdjustmentService $service Service instance
 * @return array Response data
 */
function actionFetchContracts($db, $service)
{
	$fk_soc = GETPOST('fk_soc', 'int');

	$contracts = $service->fetchActiveContracts($fk_soc > 0 ? $fk_soc : null);

	$contractList = array();
	foreach ($contracts as $contractId => $contractData) {
		$contractList[] = array(
			'id' => (int)$contractId,
			'ref' => $contractData['ref'],
			'ref_customer' => $contractData['ref_customer'],
			'socname' => $contractData['socname'],
			'active_lines' => (int)$contractData['active_lines'],
			'url' => DOL_URL_ROOT . '/contrat/card.php?id=' . $contractId,
		);
	}

	return array(
		'success' => true,
		'contracts' => $contractList,
		'total' => count($contractList),
	);
}

/**
 * Fetch active service lines for a contract
 *
 * @param DoliDB                 $db      Database handler
 * @param IndexAdjustmentService $service Service instance
 * @return array Response data
 */
function actionFetchLines($db, $service)
{
	$contractId = GETPOST('contract_id', 'int');

	if ($contractId <= 0) {
		return array('success' => false, 'message' => 'Invalid contract ID');
	}

	$lines = $service->fetchActiveServiceLines($contractId);

	$lineList = array();
	foreach ($lines as $lineId => $lineData) {
		$lineList[] = array(
			'id' => (int)$lineId,
			'fk_contrat' => (int)$contractId,
			'product_ref' => $lineData['product_ref'],
			'product_label' => $lineData['product_label'],
			'subprice' => (float)$lineData['subprice'],
			'qty' => (float)$lineData['qty'],
			'total_ht' => (float)$lineData['total_ht'],
		);
	}

	return array(
		'success' => true,
		'lines' => $lineList,
		'total' => count($lineList),
	);
}

/**
 * Preview adjustments for selected contracts (contract mode)
 *
 * @param IndexAdjustmentService $service Service instance
 * @return array Response data
 */
function actionPreview($service)
{
	$contractIds = GETPOST('contract_ids', 'array');
	$percent = GETPOST('percent', 'alpha');

	if (empty($contractIds)) {
		return array('success' => false, 'message' => 'No contracts selected');
	}

	$percent = (float)str_replace(',', '.', $percent);
	$preview = $service->previewAdjustments($contractIds, $percent);

	// Format preview for JSON
	$contracts = array();
	foreach ($preview['contracts'] as $contractId => $contractData) {
		$lines = array();
		foreach ($contractData['lines'] as $lineId => $lineData) {
			$lines[] = array(
				'id' => (int)$lineId,
				'product_ref' => $lineData['product_ref'],
				'product_label' => $lineData['product_label'],
				'qty' => (float)$lineData['qty'],
				'subprice_before' => (float)$lineData['subprice_before'],
				'subprice_after' => (float)$lineData['subprice_after'],
				'price_diff' => (float)$lineData['price_diff'],
			);
		}
		$contracts[] = array(
			'id' => (int)$contractId,
			'lines' => $lines,
			'totals' => array(
				'total_ht_before' => (float)$contractData['totals']['total_ht_before'],
				'total_ht_after' => (float)$contractData['totals']['total_ht_after'],
				'total_diff' => (float)$contractData['totals']['total_diff'],
			),
		);
	}

	return array(
		'success' => true,
		'preview' => array(
			'contracts' => $contracts,
			'totals' => array(
				'total_contracts' => (int)$preview['totals']['total_contracts'],
				'total_lines' => (int)$preview['totals']['total_lines'],
				'total_ht_before' => (float)$preview['totals']['total_ht_before'],
				'total_ht_after' => (float)$preview['totals']['total_ht_after'],
				'total_diff' => (float)$preview['totals']['total_diff'],
			),
		),
	);
}

/**
 * Preview adjustments for selected lines (lines mode)
 *
 * @param IndexAdjustmentService $service Service instance
 * @return array Response data
 */
function actionPreviewLines($service)
{
	$selectedLines = GETPOST('selected_lines', 'array');
	$percent = GETPOST('percent', 'alpha');

	if (empty($selectedLines)) {
		return array('success' => false, 'message' => 'No lines selected');
	}

	// Sanitize and validate selected_lines structure
	$sanitizedLines = array();
	foreach ($selectedLines as $contractId => $lineIds) {
		$contractId = (int)$contractId;
		if ($contractId <= 0) {
			continue;
		}

		$sanitizedLines[$contractId] = array();
		if (is_array($lineIds) && !empty($lineIds)) {
			foreach ($lineIds as $lineId) {
				$lineId = (int)$lineId;
				if ($lineId > 0) {
					$sanitizedLines[$contractId][] = $lineId;
				}
			}
		}
		// If a contract has an empty line array, fetch all its active lines
		if (empty($sanitizedLines[$contractId])) {
			$allLines = $service->fetchActiveServiceLines($contractId);
			$sanitizedLines[$contractId] = array_keys($allLines);
		}
	}
	$selectedLines = $sanitizedLines;

	if (empty($selectedLines)) {
		return array('success' => false, 'message' => 'No valid lines selected');
	}

	$percent = (float)str_replace(',', '.', $percent);
	$preview = $service->previewAdjustmentsWithLines($selectedLines, $percent);

	// Format preview for JSON
	$contracts = array();
	foreach ($preview['contracts'] as $contractId => $contractData) {
		$lines = array();
		foreach ($contractData['lines'] as $lineId => $lineData) {
			$lines[] = array(
				'id' => (int)$lineId,
				'product_ref' => $lineData['product_ref'],
				'product_label' => $lineData['product_label'],
				'qty' => (float)$lineData['qty'],
				'subprice_before' => (float)$lineData['subprice_before'],
				'subprice_after' => (float)$lineData['subprice_after'],
				'price_diff' => (float)$lineData['price_diff'],
			);
		}
		$contracts[] = array(
			'id' => (int)$contractId,
			'lines' => $lines,
			'totals' => array(
				'total_ht_before' => (float)$contractData['totals']['total_ht_before'],
				'total_ht_after' => (float)$contractData['totals']['total_ht_after'],
				'total_diff' => (float)$contractData['totals']['total_diff'],
			),
		);
	}

	return array(
		'success' => true,
		'preview' => array(
			'contracts' => $contracts,
			'totals' => array(
				'total_contracts' => (int)$preview['totals']['total_contracts'],
				'total_lines' => (int)$preview['totals']['total_lines'],
				'total_ht_before' => (float)$preview['totals']['total_ht_before'],
				'total_ht_after' => (float)$preview['totals']['total_ht_after'],
				'total_diff' => (float)$preview['totals']['total_diff'],
			),
		),
	);
}

/**
 * Execute adjustment in contract mode
 *
 * @param DoliDB                 $db      Database handler
 * @param User                   $user    Current user
 * @param IndexAdjustmentService $service Service instance
 * @return array Response data
 */
function actionExecute($db, $user, $service)
{
	global $langs;

	// Execute permission check
	if (!$user->admin && !$user->hasRight('indexadjustment', 'indexadjustment', 'execute')) {
		return array('success' => false, 'message' => $langs->trans('NotAllowed'));
	}

	$id = GETPOST('id', 'int');
	$contractIds = GETPOST('contract_ids', 'array');

	if ($id <= 0) {
		return array('success' => false, 'message' => 'Invalid adjustment ID');
	}
	if (empty($contractIds)) {
		return array('success' => false, 'message' => $langs->trans('NoContractsSelected'));
	}

	$object = new IndexAdjustment($db);
	$result = $object->fetch($id);
	if ($result <= 0) {
		return array('success' => false, 'message' => $langs->trans('ErrorAdjustmentNotFound'));
	}

	// Validate before execute
	$valResult = $object->validate($user);
	if ($valResult <= 0) {
		return array('success' => false, 'message' => $object->error ?: 'Validation failed');
	}

	$result = $service->execute($object, $user, $contractIds);
	if ($result > 0) {
		return array(
			'success' => true,
			'redirect' => dol_buildpath('/indexadjustment/card.php', 1) . '?id=' . $id,
		);
	}

	return array('success' => false, 'message' => $service->error ?: $langs->trans('ErrorExecutionFailed', ''));
}

/**
 * Execute adjustment in lines mode
 *
 * @param DoliDB                 $db      Database handler
 * @param User                   $user    Current user
 * @param IndexAdjustmentService $service Service instance
 * @return array Response data
 */
function actionExecuteLines($db, $user, $service)
{
	global $langs;

	// Execute permission check
	if (!$user->admin && !$user->hasRight('indexadjustment', 'indexadjustment', 'execute')) {
		return array('success' => false, 'message' => $langs->trans('NotAllowed'));
	}

	$id = GETPOST('id', 'int');
	$selectedLines = GETPOST('selected_lines', 'array');

	if ($id <= 0) {
		return array('success' => false, 'message' => 'Invalid adjustment ID');
	}
	if (empty($selectedLines)) {
		return array('success' => false, 'message' => $langs->trans('NoLinesSelected'));
	}

	// Sanitize and validate selected_lines structure
	$sanitizedLines = array();
	foreach ($selectedLines as $contractId => $lineIds) {
		$contractId = (int)$contractId;
		if ($contractId <= 0) {
			continue;
		}

		$sanitizedLines[$contractId] = array();
		if (is_array($lineIds) && !empty($lineIds)) {
			foreach ($lineIds as $lineId) {
				$lineId = (int)$lineId;
				if ($lineId > 0) {
					$sanitizedLines[$contractId][] = $lineId;
				}
			}
		}
		// If a contract has an empty line array, fetch all its active lines
		if (empty($sanitizedLines[$contractId])) {
			$allLines = $service->fetchActiveServiceLines($contractId);
			$sanitizedLines[$contractId] = array_keys($allLines);
		}
	}
	$selectedLines = $sanitizedLines;

	if (empty($selectedLines)) {
		return array('success' => false, 'message' => $langs->trans('NoLinesSelected'));
	}

	$object = new IndexAdjustment($db);
	$result = $object->fetch($id);
	if ($result <= 0) {
		return array('success' => false, 'message' => $langs->trans('ErrorAdjustmentNotFound'));
	}

	// Validate before execute
	$valResult = $object->validate($user);
	if ($valResult <= 0) {
		return array('success' => false, 'message' => $object->error ?: 'Validation failed');
	}

	$result = $service->executeWithLines($object, $user, $selectedLines);
	if ($result > 0) {
		return array(
			'success' => true,
			'redirect' => dol_buildpath('/indexadjustment/admin/list.php', 1),
		);
	}

	return array('success' => false, 'message' => $service->error ?: $langs->trans('ErrorExecutionFailed', ''));
}
